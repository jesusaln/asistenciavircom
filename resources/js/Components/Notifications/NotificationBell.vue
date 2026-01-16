<template>
  <div class="notification-bell-container">
    <!-- Botón de la campana -->
    <button
      @click="toggleDropdown"
      class="notification-bell-btn"
      type="button"
    >
      🔔
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
            <div class="notification-title">{{ notification.title }}</div>
            <div class="notification-message">{{ notification.message }}</div>
            <div class="notification-time">{{ formatTime(notification.created_at) }}</div>
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
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'NotificationBell',

  data() {
    return {
      notifications: [],
      unreadCount: 0,
      previousUnreadCount: 0, // Para detectar nuevas notificaciones
      showDropdown: false,
      loading: false,
      consecutiveErrors: 0,
      isPaused: false
    }
  },

  mounted() {
    this.loadUnreadCount();

    // Opcional: Actualizar contador cada 30 segundos
    this.unreadCountInterval = setInterval(() => {
      // ✅ FIX: Skip if paused due to errors
      if (!this.isPaused) {
        this.loadUnreadCount();
      }
    }, 30000);
  },

  beforeUnmount() {
    if (this.unreadCountInterval) {
      clearInterval(this.unreadCountInterval);
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
      try {
        const response = await axios.get('/notifications/unread-count', {
          timeout: 10000
        });
        const newCount = response.data.unread_count || 0;
        
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
      if (!confirm('¿Estás seguro de que quieres eliminar todas las notificaciones?')) {
        return;
      }

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
}

.notification-bell-btn {
  position: relative;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.notification-bell-btn:hover {
  background-color: rgba(0, 0, 0, 0.1);
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
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  width: 360px;
  max-height: 400px;
  z-index: 1001;
  overflow: hidden;
  margin-top: 5px;
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
  border-bottom: 1px solid #eee;
  background: #f8f9fa;
}

.dropdown-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #666;
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
  color: #666;
}

.notifications-list {
  max-height: 300px;
  overflow-y: auto;
}

.no-notifications {
  padding: 40px 20px;
  text-align: center;
  color: #666;
  font-style: italic;
}

.notification-item {
  position: relative;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
  display: flex;
  align-items: stretch;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item:hover {
  background-color: #e9ecef; /* Hover más suave */
}

/* Hover específico para leídas (blancas) */
.notification-item:not(.unread):hover {
  background-color: #f8f9fa;
}

.notification-item {
  position: relative;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
  display: flex;
  align-items: stretch;
  background-color: #f5f5f5; /* Fondo gris para no leídas por defecto */
}

.notification-item.unread {
  background-color: #f5f5f5; /* Gris para no leídas */
  border-left: 4px solid #6c757d; /* Borde gris */
}

/* Notificaciones leídas en blanco */
.notification-item:not(.unread) {
  background-color: white;
}

.notification-item.unread::before {
  content: '';
  position: absolute;
  top: 50%;
  right: 45px;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  background: #6c757d; /* Punto gris para no leídas */
  border-radius: 50%;
}

.notification-content {
  flex: 1;
  padding: 15px 20px;
  cursor: pointer;
}

.notification-title {
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin-bottom: 4px;
  line-height: 1.3;
}

.notification-message {
  font-size: 13px;
  color: #666;
  margin-bottom: 6px;
  line-height: 1.4;
}

.notification-time {
  font-size: 11px;
  color: #999;
}

.remove-notification-btn {
  background: none;
  border: none;
  color: #999;
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
  border-top: 1px solid #eee;
  background: #f8f9fa;
  display: flex;
  gap: 8px;
}

.mark-all-read-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  flex: 1;
  transition: background-color 0.2s;
}

.mark-all-read-btn:hover {
  background: #0056b3;
}

.remove-all-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  flex: 1;
  transition: background-color 0.2s;
}

.remove-all-btn:hover {
  background: #c82333;
}

/* Scrollbar personalizada */
.notifications-list::-webkit-scrollbar {
  width: 6px;
}

.notifications-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notifications-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.notifications-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>

