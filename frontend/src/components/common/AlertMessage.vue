<template>
  <transition name="alert-fade">
    <div
      v-if="visible"
      class="alert"
      :class="`alert--${tipo}`"
      role="alert"
    >
      <span class="alert__icon">{{ iconos[tipo] }}</span>
      <div class="alert__content">
        <p class="alert__message">{{ mensaje }}</p>
      </div>
      <button class="alert__close" @click="cerrar" aria-label="Cerrar">✕</button>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  mensaje: {
    type: String,
    required: true,
  },
  tipo: {
    type: String,
    default: 'info',
    validator: (v) => ['success', 'error', 'warning', 'info'].includes(v),
  },
  duracion: {
    type: Number,
    default: 5000,
  },
  autoClose: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['close'])

const visible = ref(true)

const iconos = {
  success: '✅',
  error: '❌',
  warning: '⚠️',
  info: 'ℹ️',
}

let timer = null

function cerrar() {
  visible.value = false
  emit('close')
}

onMounted(() => {
  if (props.autoClose && props.duracion > 0) {
    timer = setTimeout(cerrar, props.duracion)
  }
})

watch(() => props.mensaje, () => {
  visible.value = true
  if (timer) clearTimeout(timer)
  if (props.autoClose && props.duracion > 0) {
    timer = setTimeout(cerrar, props.duracion)
  }
})
</script>

<style scoped>
.alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-md);
  margin-bottom: 1rem;
  animation: fadeIn 0.3s ease-out;
}

.alert--success {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  color: var(--color-success);
}

.alert--error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: var(--color-error);
}

.alert--warning {
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.2);
  color: var(--color-warning);
}

.alert--info {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: var(--color-info);
}

.alert__icon {
  font-size: 1.125rem;
  flex-shrink: 0;
}

.alert__content {
  flex: 1;
}

.alert__message {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.4;
}

.alert__close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font-size: 0.875rem;
  opacity: 0.7;
  transition: opacity 0.2s;
  padding: 0.25rem;
}

.alert__close:hover {
  opacity: 1;
}

.alert-fade-enter-active,
.alert-fade-leave-active {
  transition: all 0.3s ease;
}

.alert-fade-enter-from,
.alert-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
