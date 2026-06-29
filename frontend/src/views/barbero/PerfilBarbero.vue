<template>
  <div class="perfil animate-fade-in">
    <div class="perfil__header">
      <h1 class="perfil__title">Mi Perfil</h1>
      <span class="perfil__badge badge-active" v-if="perfil">
        ● Solo lectura
      </span>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="perfil__loading">
      <div class="perfil__spinner"></div>
      <p>Cargando perfil...</p>
    </div>

    <!-- Error -->
    <AlertMessage v-if="error" :mensaje="error" tipo="error" />

    <!-- Perfil -->
    <div v-if="perfil && !cargando" class="perfil__content">
      <div class="perfil__card glass-card">
        <div class="perfil__avatar">
          <span class="perfil__avatar-text">{{ iniciales }}</span>
        </div>
        <h2 class="perfil__nombre">{{ perfil.nombre_completo }}</h2>
        <p class="perfil__correo">{{ perfil.correo }}</p>
      </div>

      <div class="perfil__details glass-card">
        <h3 class="perfil__section-title">Información Personal</h3>
        <div class="perfil__grid">
          <div class="perfil__field">
            <span class="perfil__field-label">Primer Nombre</span>
            <span class="perfil__field-value">{{ perfil.nombre1 }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Segundo Nombre</span>
            <span class="perfil__field-value">{{ perfil.nombre2 || '—' }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Primer Apellido</span>
            <span class="perfil__field-value">{{ perfil.apellido1 }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Segundo Apellido</span>
            <span class="perfil__field-value">{{ perfil.apellido2 || '—' }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Correo Electrónico</span>
            <span class="perfil__field-value">{{ perfil.correo }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Estado</span>
            <span :class="perfil.estado === 'Activo' ? 'badge-active' : 'badge-inactive'">
              ● {{ perfil.estado }}
            </span>
          </div>
        </div>
      </div>

      <div class="perfil__details glass-card">
        <h3 class="perfil__section-title">Antigüedad</h3>
        <div class="perfil__grid">
          <div class="perfil__field">
            <span class="perfil__field-label">Fecha de Ingreso</span>
            <span class="perfil__field-value">{{ formatearFecha(perfil.fecha_ingreso) }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Antigüedad</span>
            <span class="perfil__field-value perfil__field-value--highlight">
              {{ perfil.antiguedad_dias }} días
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { formatearFecha } from '../../utils/helpers.js'

const perfil = ref(null)
const cargando = ref(true)
const error = ref('')

const iniciales = computed(() => {
  if (!perfil.value) return '?'
  const n = perfil.value.nombre1 ? perfil.value.nombre1.charAt(0) : ''
  const a = perfil.value.apellido1 ? perfil.value.apellido1.charAt(0) : ''
  return (n + a).toUpperCase()
})

onMounted(async () => {
  try {
    const data = await barberoService.getPerfilBarbero()
    perfil.value = data.barbero
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar el perfil'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.perfil {
  max-width: 800px;
}

.perfil__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.perfil__title {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 700;
}

.perfil__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.perfil__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold-400);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.perfil__content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.perfil__card {
  padding: 2rem;
  text-align: center;
}

.perfil__avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-500));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.perfil__avatar-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-bg-primary);
}

.perfil__nombre {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.perfil__correo {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.perfil__details {
  padding: 1.5rem;
}

.perfil__section-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-gold-400);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border);
}

.perfil__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.perfil__field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.perfil__field-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.perfil__field-value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-primary);
}

.perfil__field-value--highlight {
  color: var(--color-gold-400);
  font-weight: 700;
  font-size: 1.125rem;
}
</style>
