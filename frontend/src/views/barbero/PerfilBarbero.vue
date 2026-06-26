<template>
  <div class="perfil animate-fade-in">
    <div class="perfil__header">
      <h1 class="perfil__title">Mi Perfil</h1>
      <span class="perfil__badge" v-if="perfil">
        🔒 Solo lectura
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
      <!-- Card principal con avatar e iniciales -->
      <div class="perfil__card glass-card">
        <div class="perfil__avatar">
          <span class="perfil__avatar-text">{{ iniciales }}</span>
        </div>
        <h2 class="perfil__nombre">{{ perfil.nombre_completo }}</h2>
        <p class="perfil__correo">{{ perfil.correo }}</p>
        <span :class="perfil.estado === 'Activo' ? 'badge-active' : 'badge-inactive'" style="margin-top: 0.5rem;">
          ● {{ perfil.estado }}
        </span>
      </div>

      <!-- Información Personal (solo lectura) -->
      <div class="perfil__details glass-card">
        <h3 class="perfil__section-title">👤 Información Personal</h3>
        <div class="perfil__grid">
          <div class="perfil__field">
            <span class="perfil__field-label">Nombre Completo</span>
            <span class="perfil__field-value">{{ perfil.nombre_completo }}</span>
          </div>
          <div class="perfil__field">
            <span class="perfil__field-label">Correo Electrónico</span>
            <span class="perfil__field-value">{{ perfil.correo }}</span>
          </div>
        </div>
      </div>

      <!-- Antigüedad -->
      <div class="perfil__details glass-card">
        <h3 class="perfil__section-title">📅 Antigüedad</h3>
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
        <p class="perfil__antiguedad-nota">
          La antigüedad se calcula automáticamente restando la fecha de ingreso a la fecha actual en días completos.
        </p>
      </div>

      <!-- Nota de solo lectura -->
      <div class="perfil__readonly-notice">
        <span>🔒</span>
        <p>Esta información se encuentra en modo de solo lectura sin opciones de edición o modificación de ningún campo. Contacte al administrador si requiere algún cambio.</p>
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

.perfil__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.875rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-bronce);
  background: var(--color-oro-suave);
  border: 1px solid var(--color-bronce);
  border-radius: 9999px;
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
  border-top-color: var(--color-azul-real);
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
  background: linear-gradient(135deg, var(--color-azul-oscuro), var(--color-azul-real));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.perfil__avatar-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
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
  color: var(--color-azul-real);
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
  font-weight: 600;
  color: var(--color-bronce);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.perfil__field-value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-primary);
}

.perfil__field-value--highlight {
  color: var(--color-azul-real);
  font-weight: 700;
  font-size: 1.125rem;
}

.perfil__antiguedad-nota {
  margin-top: 1rem;
  font-size: 0.75rem;
  color: var(--color-bronce);
  font-style: italic;
}

.perfil__readonly-notice {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: var(--color-oro-suave);
  border: 1px solid var(--color-bronce);
  border-radius: var(--radius-md);
  font-size: 0.8125rem;
  color: var(--color-azul-oscuro);
  line-height: 1.5;
}

.perfil__readonly-notice span {
  font-size: 1.25rem;
  flex-shrink: 0;
}
</style>
