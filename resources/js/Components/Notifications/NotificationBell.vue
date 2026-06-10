<template>
  <div class="notification-bell-container" :class="{ 'is-active': showDropdown }">
    <!-- Botón de la campana -->
    <button
      @click="toggleDropdown"
      class="notification-bell-btn group/bell"
      type="button"
    >
      <FontAwesomeIcon 
        :icon="faBell" 
        class="text-[var(--ui-text-soft)] group-hover/bell:text-[var(--ui-accent)] transition-colors duration-300"
      />
      <!-- Badge del contador -->
      <span
        v-if="unreadCount > 0"
        class="notification-badge"
      >
        {{ unreadCount }}
      </span>
    </button>

    <!-- Dropdown de notificaciones -->
    <div
      v-if="showDropdown"
      class="notifications-dropdown"
    >
      <!-- Header del dropdown -->
      <div class="dropdown-header">
        <h4>Notificaciones</h4>
        <button @click="closeDropdown" class="close-btn">×</button>
      </div>

      <!-- Selector de Sonido -->
      <div class="sound-settings-bar">
        <div class="sound-settings-label">
          <i class="fas fa-volume-up text-[var(--ui-accent)] mr-1"></i>
          <span>Sonido:</span>
        </div>
        <div class="sound-settings-control">
          <select 
            v-model="selectedSound" 
            @change="saveSound"
            class="sound-select"
          >
            <option value="/sounds/modern-chime.mp3">Modern Chime 🔔</option>
            <option value="/sounds/classic-ding.mp3">Classic Ding 🛎️</option>
            <option value="/sounds/soft-pop.mp3">Soft Pop 🫧</option>
            <option value="/sounds/digital-alert.mp3">Digital Alert ⚡</option>
            <option value="/sounds/bubbly-pop.mp3">Bubbly Pop (Original) 🧼</option>
          </select>
          <button 
            @click="testSound"
            class="test-sound-btn"
            title="Probar sonido"
            type="button"
          >
            <i class="fas fa-play"></i>
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading">
        Cargando notificaciones...
      </div>

      <!-- Lista de notificaciones -->
      <div v-else class="notifications-list">
        <div v-if="notifications.length === 0" class="no-notifications">
          No hay notificaciones
        </div>

        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'unread': !notification.read }"
        >
          <div class="notification-content" @click="markAsRead(notification.id)">
            <div class="flex items-start gap-3">
              <div class="notification-icon mt-1">
                <i v-if="notification.icon" :class="notification.icon" class="text-[var(--ui-accent)]"></i>
                <FontAwesomeIcon v-else :icon="faBell" class="text-[var(--ui-accent)]" />
              </div>
              <div class="flex-1">
                <div class="notification-title">{{ notification.title }}</div>
                <div class="notification-message">{{ notification.message }}</div>
                <div class="notification-time">{{ formatTime(notification.created_at) }}</div>
              </div>
            </div>
          </div>
          <button
            @click.stop="removeNotification(notification.id)"
            class="remove-notification-btn"
            title="Eliminar notificación"
          >
            ×
          </button>
        </div>
      </div>

      <!-- Footer del dropdown -->
      <div v-if="notifications.length > 0" class="dropdown-footer">
        <button @click="markAllAsRead" class="mark-all-read-btn">
          Marcar todas como leídas
        </button>
        <button @click="removeAllNotifications" class="remove-all-btn">
          Eliminar todas
        </button>
      </div>
    </div>

    <!-- Overlay para cerrar el dropdown -->
    <div
      v-if="showDropdown"
      class="dropdown-overlay"
      @click="closeDropdown"
    ></div>
  </div>
</template>

<script>
import { useFormatters } from '@/Composables/useFormatters';
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from '@/Utils/Swal'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBell } from '@fortawesome/free-solid-svg-icons'

export default {
  name: 'NotificationBell',

  components: {
    FontAwesomeIcon
  },

  setup() {
    const page = usePage()
    return {
      faBell,
      page
    }
  },

  emits: ['notification-clicked'],

  props: {
    autoRefresh: {
      type: Boolean,
      default: true
    },
    refreshInterval: {
      type: Number,
      default: 60000 // 60 segundos por defecto
    }
  },

  data() {
    return {
      notifications: [],
      unreadCount: 0,
      previousUnreadCount: 0, // Para detectar nuevas notificaciones
      showDropdown: false,
      loading: false,
      consecutiveErrors: 0,
      isPaused: false,
      selectedSound: localStorage.getItem('cdd_notification_sound') || '/sounds/modern-chime.mp3'
    }
  },

  mounted() {
    this.loadUnreadCount();

    if (this.autoRefresh) {
      this.unreadCountInterval = setInterval(() => {
        // Solo consultar si la pestaña está visible para ahorrar recursos
        if (!this.isPaused && document.visibilityState === 'visible') {
          this.loadUnreadCount();
        }
      }, this.refreshInterval);
    }

    // 🔔 Conexión WebSockets en Tiempo Real
    const userId = this.page?.props?.auth?.user?.id;
    if (window.Echo && userId) {
      window.Echo.private(`App.Models.User.${userId}`)
        .listen('UserNotificationCreated', (e) => {
          console.log('[Echo] Notificación en tiempo real recibida:', e);
          this.unreadCount++;
          this.previousUnreadCount = this.unreadCount;
          
          // Prepend a la lista si ya está cargada
          this.notifications.unshift(e);
          
          // Toast visual y de audio
          if (window.$toast) {
            window.$toast.info(e.message, `🔔 ${e.title}`);
          }
          try {
            const soundPath = localStorage.getItem('cdd_notification_sound') || '/sounds/modern-chime.mp3';
            const audio = new Audio(soundPath);
            audio.play();
          } catch (err) {}
        });
    }
  },

  beforeUnmount() {
    if (this.unreadCountInterval) {
      clearInterval(this.unreadCountInterval);
    }
    const userId = this.page?.props?.auth?.user?.id;
    if (window.Echo && userId) {
      window.Echo.leave(`App.Models.User.${userId}`);
    }
  },

  methods: {
    async toggleDropdown() {
      if (!this.showDropdown) {
        // Abrir dropdown
        this.showDropdown = true;

        // Cargar notificaciones si no están cargadas
        if (this.notifications.length === 0) {
          await this.loadNotifications();
        }
      } else {
        // Cerrar dropdown
        this.closeDropdown();
      }
    },

    closeDropdown() {
      this.showDropdown = false;
    },

    async loadNotifications() {
      this.loading = true;

      try {
        const response = await axios.get('/notifications');

        // Asignar datos
        this.notifications = response.data.notifications || [];
        this.unreadCount = response.data.unread_count || 0;

      } catch (error) {
        console.error('Error loading notifications:', error);
        this.handleAuthError(error);
      } finally {
        this.loading = false;
      }
    },

    async loadUnreadCount() {
      // ✅ OPTIMIZATION: Tab Sharing
      // Check if another tab has fetched the count in the last 55 seconds
      const now = Date.now();
      const lastFetch = parseInt(localStorage.getItem('notification_last_fetch') || '0');
      const cachedCount = parseInt(localStorage.getItem('notification_unread_count') || '0');

      if (now - lastFetch < 55000 && !this.loading) {
        this.unreadCount = cachedCount;
        this.previousUnreadCount = cachedCount;
        return;
      }

      try {
        const response = await axios.get('/notifications/unread-count', {
          timeout: 10000,
          skipGlobalErrorHandler: true
        });
        const newCount = response.data.unread_count || 0;
        
        // Update Local Storage for other tabs
        localStorage.setItem('notification_last_fetch', Date.now().toString());
        localStorage.setItem('notification_unread_count', newCount.toString());

        // Mostrar toast si hay nuevas notificaciones
        if (newCount > this.previousUnreadCount && this.previousUnreadCount > 0) {
          const newNotifications = newCount - this.previousUnreadCount;
          if (window.$toast) {
            window.$toast.info(
              `Tienes ${newNotifications} nueva${newNotifications > 1 ? 's' : ''} notificación${newNotifications > 1 ? 'es' : ''}`,
              '🔔 Nueva notificación'
            );
          }
        }
        
        this.previousUnreadCount = newCount;
        this.unreadCount = newCount;
        
        // Reset error counter on success
        this.consecutiveErrors = 0;
        this.isPaused = false;

      } catch (error) {
        this.consecutiveErrors++;
        
        if (this.consecutiveErrors <= 3) {
          console.warn('Error loading unread count (attempt ' + this.consecutiveErrors + '):', error.message || error);
        }
        
        // After 3 consecutive errors, pause polling for 5 minutes
        if (this.consecutiveErrors >= 3 && !this.isPaused) {
          this.isPaused = true;
          console.warn('Notification polling paused due to network errors. Will resume in 5 minutes.');
          
          // Resume after 5 minutes
          setTimeout(() => {
            this.isPaused = false;
            this.consecutiveErrors = 0;
            console.info('Notification polling resumed.');
          }, 300000); // 5 minutes
        }
        
        this.handleAuthError(error);
      }
    },

    async markAsRead(notificationId) {
      const notification = this.notifications.find(n => n.id === notificationId)
      if (notification?.action_url) {
        this.$emit('notification-clicked', notification)
      }

      try {
        await axios.post('/notifications/mark-as-read', { ids: [notificationId] });

        // Remover de la lista localmente para evitar acumulación (según petición usuario)
        const index = this.notifications.findIndex(n => n.id === notificationId);
        if (index > -1) {
            const notification = this.notifications[index];
            if (!notification.read) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            // Eliminar de la vista
            this.notifications.splice(index, 1);
        }

      } catch (error) {
        console.error('Error marking notification as read:', error);
        this.handleAuthError(error);
      }
    },

    async markAllAsRead() {
      try {
        const unreadNotifications = this.notifications.filter(n => !n.read);

        if (unreadNotifications.length === 0) {
          return;
        }

        await axios.post('/notifications/mark-all-as-read');

        // Eliminar todas las notificaciones de la lista para limpiar
        this.notifications = [];
        this.unreadCount = 0;

      } catch (error) {
        console.error('Error marking all notifications as read:', error);
        this.handleAuthError(error);
        if (!error.response || (error.response.status !== 401 && error.response.status !== 419)) {
          Swal.fire({
            icon: 'error',
            title: 'Error al marcar notificaciones',
            text: 'No se pudieron marcar las notificaciones como leídas'
          });
        }
      }
    },

    async removeNotification(notificationId) {
      try {
        // Verificar si el endpoint existe
        await axios.delete(`/notifications/${notificationId}`);

        // Remover la notificación localmente
        const notificationIndex = this.notifications.findIndex(n => n.id === notificationId);
        if (notificationIndex > -1) {
          const notification = this.notifications[notificationIndex];

          // Si era no leída, decrementar contador
          if (!notification.read) {
            this.unreadCount = Math.max(0, this.unreadCount - 1);
          }

          // Remover de la lista
          this.notifications.splice(notificationIndex, 1);
        }

      } catch (error) {
        if (error.response?.status === 404) {
          Swal.fire({
            icon: 'warning',
            title: 'Funcionalidad no disponible',
            text: 'La funcionalidad de eliminar notificaciones no está disponible en el servidor'
          });
        } else {
          console.error('Error removing notification:', error);
          this.handleAuthError(error);
          if (!error.response || (error.response.status !== 401 && error.response.status !== 419)) {
            Swal.fire({
              icon: 'error',
              title: 'Error al eliminar notificación',
              text: 'No se pudo eliminar la notificación'
            });
          }
        }
      }
    },

    async removeAllNotifications() {
      const { isConfirmed } = await Swal.fire({ title: '¿Eliminar todas?', text: '¿Estás seguro de que quieres eliminar todas las notificaciones?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar todo', cancelButtonText: 'Cancelar' })
      if (!isConfirmed) return

      try {
        // Si no tienes endpoint para eliminar todas, eliminar una por una
        const deletePromises = this.notifications.map(notification =>
          axios.delete(`/notifications/${notification.id}`)
        );

        await Promise.all(deletePromises);

        // Limpiar todo localmente
        this.notifications = [];
        this.unreadCount = 0;

      } catch (error) {
        console.error('Error removing all notifications:', error);
        this.handleAuthError(error);
        if (!error.response || (error.response.status !== 401 && error.response.status !== 419)) {
          Swal.fire({
            icon: 'error',
            title: 'Error al eliminar notificaciones',
            text: 'No se pudieron eliminar todas las notificaciones'
          });
        }
      }
    },

    saveSound() {
      localStorage.setItem('cdd_notification_sound', this.selectedSound);
    },

    testSound() {
      try {
        const audio = new Audio(this.selectedSound);
        audio.play();
      } catch (err) {
        console.error('Error playing sound test:', err);
      }
    },

    formatTime(timestamp) {
      if (!timestamp) return '';

      const date = new Date(timestamp);
      const now = new Date();
      const diff = now - date;

      // Menos de 1 minuto
      if (diff < 60000) {
        return 'Hace un momento';
      }

      // Menos de 1 hora
      if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
      }

      // Menos de 24 horas
      if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
      }

      // Más de 24 horas
      return date.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    handleAuthError(error) {
      if (error.response?.status === 401) {
        // Usuario no autenticado - redirigir al login
        console.warn('Usuario no autenticado, redirigiendo al login...');
        window.location.href = '/login';
      } else if (error.response?.status === 419) {
        // CSRF token mismatch - refrescar la página
        console.warn('CSRF token mismatch, refrescando la página...');
        window.location.reload();
      }
    }
  }
}
</script>

<style scoped>
.notification-bell-container {
  position: relative;
  display: inline-block;
  z-index: 10;
}

.notification-bell-container.is-active {
  z-index: 1002;
}

.notification-bell-btn {
  position: relative;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-bell-btn:hover {
  background-color: var(--ui-surface-soft);
}

.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background: #ff4444;
  color: white;
  border-radius: 50%;
  padding: 2px 6px;
  font-size: 12px;
  font-weight: bold;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.notifications-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: var(--ui-surface);
  border: 1px solid var(--ui-border);
  border-radius: 1.5rem;
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
  width: 380px;
  max-height: 500px;
  z-index: 1001;
  overflow: hidden;
  animation: dropdownFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes dropdownFadeIn {
  from { opacity: 0; transform: translateY(-10px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  background: transparent;
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid var(--ui-border);
  background: var(--ui-surface-soft);
}

.sound-settings-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 20px;
  background-color: var(--ui-surface-soft);
  border-bottom: 1px solid var(--ui-border);
  font-size: 12px;
  color: var(--ui-text-soft);
}

.sound-settings-label {
  display: flex;
  align-items: center;
}

.sound-settings-control {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sound-select {
  background-color: var(--ui-surface);
  color: var(--ui-text);
  border: 1px solid var(--ui-border);
  border-radius: 0.375rem;
  padding: 3px 8px;
  font-size: 11px;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s;
}

.sound-select:focus {
  border-color: var(--ui-accent);
}

.test-sound-btn {
  background: none;
  border: none;
  color: var(--ui-accent);
  cursor: pointer;
  padding: 4px 6px;
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}

.test-sound-btn:hover {
  opacity: 0.8;
}

.dropdown-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: var(--ui-text);
}

.close-btn {
  font-size: 20px;
  cursor: pointer;
  color: var(--ui-text-soft);
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.close-btn:hover {
  background-color: #e9ecef;
}

.loading {
  padding: 20px;
  text-align: center;
  color: var(--ui-text-soft);
}

.notifications-list {
  max-height: 300px;
  overflow-y: auto;
}

.no-notifications {
  padding: 40px 20px;
  text-align: center;
  color: var(--ui-text-soft);
  font-style: italic;
}

.notification-item {
  position: relative;
  border-bottom: 1px solid var(--ui-border);
  transition: all 0.2s;
  display: flex;
  align-items: stretch;
  background-color: var(--ui-surface);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item.unread {
  background-color: var(--ui-surface-soft);
  border-left: 4px solid var(--ui-accent); /* Borde de énfasis */
}

/* Notificaciones leídas en blanco / superficie normal */
.notification-item:not(.unread) {
  background-color: var(--ui-surface);
}

.notification-item.unread::before {
  content: '';
  position: absolute;
  top: 50%;
  right: 45px;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  background: var(--ui-accent); /* Punto de acento para no leídas */
  border-radius: 50%;
}

.notification-content {
  flex: 1;
  padding: 15px 20px;
  cursor: pointer;
}

.notification-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ui-surface-soft);
  border-radius: 50%;
  font-size: 14px;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.flex { display: flex; }
.items-start { align-items: flex-start; }
.gap-3 { gap: 0.75rem; }
.flex-1 { flex: 1 1 0%; }
.mt-1 { margin-top: 0.25rem; }

.notification-title {
  font-weight: 600;
  font-size: 14px;
  color: var(--ui-text);
  margin-bottom: 4px;
  line-height: 1.3;
}

.notification-message {
  font-size: 13px;
  color: var(--ui-text-soft);
  margin-bottom: 6px;
  line-height: 1.4;
}

.notification-time {
  font-size: 11px;
  color: var(--ui-text-muted, var(--ui-text-soft));
  opacity: 0.8;
}

.remove-notification-btn {
  background: none;
  border: none;
  color: var(--ui-text-soft);
  cursor: pointer;
  padding: 15px 12px;
  font-size: 18px;
  line-height: 1;
  transition: color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
}

.remove-notification-btn:hover {
  color: #ff4444;
  background-color: rgba(255, 68, 68, 0.1);
}

.dropdown-footer {
  padding: 12px 20px;
  border-top: 1px solid var(--ui-border);
  background: var(--ui-surface-soft);
  display: flex;
  gap: 12px;
}

.mark-all-read-btn {
  background: var(--ui-accent, #007bff);
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 0.5rem;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  flex: 1;
  transition: all 0.2s;
}

.mark-all-read-btn:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
}

.remove-all-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 0.5rem;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  flex: 1;
  transition: all 0.2s;
}

.remove-all-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
}

/* Scrollbar personalizada */
.notifications-list::-webkit-scrollbar {
  width: 6px;
}

.notifications-list::-webkit-scrollbar-track {
  background: var(--ui-surface);
}

.notifications-list::-webkit-scrollbar-thumb {
  background: var(--ui-border);
  border-radius: 3px;
}

.notifications-list::-webkit-scrollbar-thumb:hover {
  background: var(--ui-text-soft);
}
</style>

