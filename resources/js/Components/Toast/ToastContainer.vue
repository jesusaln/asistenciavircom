<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="[toast.type, { 'toast-exiting': toast.exiting }]"
          @click="removeToast(toast.id)"
          @mouseenter="pauseToast(toast.id)"
          @mouseleave="resumeToast(toast.id)"
        >
          <!-- Icono -->
          <div class="toast-icon">
            <span v-if="toast.type === 'success'">✓</span>
            <span v-else-if="toast.type === 'error'">✕</span>
            <span v-else-if="toast.type === 'warning'">⚠</span>
            <span v-else>ℹ</span>
          </div>
          
          <!-- Contenido -->
          <div class="toast-content">
            <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
            <div class="toast-message">{{ toast.message }}</div>
          </div>
          
          <!-- Barra de progreso -->
          <div 
            class="toast-progress"
            :style="{ animationDuration: toast.duration + 'ms' }"
            :class="{ 'paused': toast.paused }"
          ></div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const toasts = ref([])
let toastId = 0

const addToast = (options) => {
  const id = ++toastId
  const duration = options.duration || 5000
  
  const toast = {
    id,
    type: options.type || 'info',
    title: options.title || null,
    message: options.message || '',
    duration,
    paused: false,
    timeoutId: null,
    remainingTime: duration,
    startTime: Date.now()
  }
  
  toasts.value.push(toast)
  
  // Auto-remove after duration
  toast.timeoutId = setTimeout(() => {
    removeToast(id)
  }, duration)
  
  // Limit to 5 toasts max
  if (toasts.value.length > 5) {
    const oldest = toasts.value[0]
    removeToast(oldest.id)
  }
  
  return id
}

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    const toast = toasts.value[index]
    if (toast.timeoutId) {
      clearTimeout(toast.timeoutId)
    }
    toasts.value.splice(index, 1)
  }
}

const pauseToast = (id) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast && toast.timeoutId) {
    clearTimeout(toast.timeoutId)
    toast.paused = true
    toast.remainingTime = toast.remainingTime - (Date.now() - toast.startTime)
  }
}

const resumeToast = (id) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast && toast.paused) {
    toast.paused = false
    toast.startTime = Date.now()
    toast.timeoutId = setTimeout(() => {
      removeToast(id)
    }, toast.remainingTime)
  }
}

// Expose methods globally
const showSuccess = (message, title = null) => addToast({ type: 'success', message, title })
const showError = (message, title = null) => addToast({ type: 'error', message, title, duration: 7000 })
const showWarning = (message, title = null) => addToast({ type: 'warning', message, title })
const showInfo = (message, title = null) => addToast({ type: 'info', message, title })

// Global event listener for custom events
const handleToastEvent = (event) => {
  const { type, message, title } = event.detail
  addToast({ type, message, title })
}

onMounted(() => {
  window.addEventListener('show-toast', handleToastEvent)
  
  // Expose toast methods globally
  window.$toast = {
    success: showSuccess,
    error: showError,
    warning: showWarning,
    info: showInfo
  }
})

onUnmounted(() => {
  window.removeEventListener('show-toast', handleToastEvent)
})

// Expose for use in parent components
defineExpose({
  success: showSuccess,
  error: showError,
  warning: showWarning,
  info: showInfo
})
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  min-width: 320px;
  max-width: 420px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 2px 10px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  border-left: 4px solid;
  transition: transform 0.2s, opacity 0.2s;
}

.toast:hover {
  transform: translateX(-5px);
}

/* Types */
.toast.success {
  border-left-color: #10b981;
}

.toast.error {
  border-left-color: #ef4444;
}

.toast.warning {
  border-left-color: #f59e0b;
}

.toast.info {
  border-left-color: #3b82f6;
}

/* Icon */
.toast-icon {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  flex-shrink: 0;
}

.toast.success .toast-icon {
  background: #d1fae5;
  color: #059669;
}

.toast.error .toast-icon {
  background: #fee2e2;
  color: #dc2626;
}

.toast.warning .toast-icon {
  background: #fef3c7;
  color: #d97706;
}

.toast.info .toast-icon {
  background: #dbeafe;
  color: #2563eb;
}

/* Content */
.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 2px;
}

.toast-message {
  font-size: 13px;
  color: #4b5563;
  line-height: 1.4;
}

/* Progress bar */
.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.3;
  animation: shrink linear forwards;
}

.toast.success .toast-progress { color: #10b981; }
.toast.error .toast-progress { color: #ef4444; }
.toast.warning .toast-progress { color: #f59e0b; }
.toast.info .toast-progress { color: #3b82f6; }

.toast-progress.paused {
  animation-play-state: paused;
}

@keyframes shrink {
  from { width: 100%; }
  to { width: 0%; }
}

/* Transitions */
.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
  animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideOut {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(100%);
  }
}

/* Mobile */
@media (max-width: 640px) {
  .toast-container {
    top: auto;
    bottom: 20px;
    left: 20px;
    right: 20px;
  }
  
  .toast {
    min-width: auto;
    max-width: none;
  }
}
</style>
