<template>
  <div class="perfil-admin animate-fade-in">
    <div class="perfil-admin__header">
      <div class="perfil-admin__back">
        <router-link to="/admin/barberos" class="btn-secondary perfil-admin__back-btn">
          ← Volver
        </router-link>
        <h1 class="perfil-admin__title">Perfil del Barbero</h1>
      </div>
      <div class="perfil-admin__actions" v-if="barbero">
        <span class="perfil-admin__badge">🔒 Solo lectura</span>
        <router-link
          :to="`/admin/barberos/${id}/editar`"
          class="btn-primary"
          id="btn-editar-barbero"
        >
          ✏️ Editar
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="perfil-admin__loading">
      <div class="perfil-admin__spinner"></div>
      <p>Cargando perfil...</p>
    </div>

    <!-- Error -->
    <AlertMessage v-if="error" :mensaje="error" tipo="error" />

    <!-- Perfil completo -->
    <div v-if="barbero && !cargando" class="perfil-admin__content">
      <!-- Card principal -->
      <div class="perfil-admin__card glass-card">
        <div class="perfil-admin__avatar">
          <span>{{ iniciales }}</span>
        </div>
        <h2 class="perfil-admin__nombre">{{ barbero.nombre_completo }}</h2>
        <p class="perfil-admin__correo">{{ barbero.correo }}</p>
        <span :class="barbero.estado_activo ? 'badge-active' : 'badge-inactive'" style="margin-top:0.5rem;">
          ● {{ barbero.estado }}
        </span>
      </div>

      <!-- Información personal -->
      <div class="perfil-admin__section glass-card">
        <h3 class="perfil-admin__section-title">👤 Información Personal</h3>
        <div class="perfil-admin__grid">
          <div class="perfil-admin__field">
            <span class="perfil-admin__label">Nombre Completo</span>
            <span class="perfil-admin__value">{{ barbero.nombre_completo }}</span>
          </div>
          <div class="perfil-admin__field">
            <span class="perfil-admin__label">Correo Electrónico</span>
            <span class="perfil-admin__value">{{ barbero.correo }}</span>
          </div>
          <div class="perfil-admin__field">
            <span class="perfil-admin__label">Estado</span>
            <span :class="barbero.estado_activo ? 'badge-active' : 'badge-inactive'">
              ● {{ barbero.estado }}
            </span>
          </div>
        </div>
      </div>

      <!-- Antigüedad -->
      <div class="perfil-admin__section glass-card">
        <h3 class="perfil-admin__section-title">📅 Antigüedad</h3>
        <div class="perfil-admin__grid">
          <div class="perfil-admin__field">
            <span class="perfil-admin__label">Fecha de Ingreso</span>
            <span class="perfil-admin__value">{{ formatearFecha(barbero.fecha_ingreso) }}</span>
          </div>
          <div class="perfil-admin__field">
            <span class="perfil-admin__label">Antigüedad</span>
            <span class="perfil-admin__value perfil-admin__value--gold">
              {{ barbero.antiguedad_dias }} días
            </span>
          </div>
        </div>
        <p class="perfil-admin__calc-note">
          La antigüedad se calcula automáticamente restando la fecha de ingreso a la fecha actual en días completos.
        </p>
      </div>

      <!-- Configuración de horario -->
      <div class="perfil-admin__section glass-card">
        <h3 class="perfil-admin__section-title">🕐 Configuración de Horario Asignada</h3>
        <div v-if="barbero.horarios && barbero.horarios.length" class="perfil-admin__horarios">
          <div
            v-for="horario in barbero.horarios"
            :key="horario.dia_semana"
            class="perfil-admin__horario-item"
            :class="{ 'perfil-admin__horario-item--descanso': horario.dia_descanso }"
          >
            <span class="perfil-admin__horario-dia">{{ horario.dia_semana }}</span>
            <span v-if="!horario.dia_descanso" class="perfil-admin__horario-horas">
              {{ formatearHora(horario.hora_entrada) }} — {{ formatearHora(horario.hora_salida) }}
            </span>
            <span v-else class="perfil-admin__horario-descanso">Descanso</span>
          </div>
        </div>
        <p v-else class="perfil-admin__empty">
          No hay horario asignado para este barbero.
        </p>
      </div>

      <!-- Nota de solo lectura -->
      <div class="perfil-admin__readonly-notice">
        <span>🔒</span>
        <p>Esta sección se encuentra en modo de solo lectura. Para editar la información del barbero, haga clic en el botón <strong>Editar</strong> en la parte superior.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { formatearFecha, formatearHora } from '../../utils/helpers.js'

const route = useRoute()
const id = route.params.id

const barbero = ref(null)
const cargando = ref(true)
const error = ref('')

const iniciales = computed(() => {
  if (!barbero.value) return '?'
  const n = barbero.value.nombre1 ? barbero.value.nombre1.charAt(0) : ''
  const a = barbero.value.apellido1 ? barbero.value.apellido1.charAt(0) : ''
  return (n + a).toUpperCase()
})

onMounted(async () => {
  try {
    const data = await barberoService.getBarbero(id)
    barbero.value = data.barbero
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar el perfil del barbero'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.perfil-admin {
  max-width: 800px;
}

.perfil-admin__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.perfil-admin__back {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.perfil-admin__back-btn {
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
}

.perfil-admin__title {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 700;
}

.perfil-admin__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.perfil-admin__badge {
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

.perfil-admin__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.perfil-admin__spinner {
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

.perfil-admin__content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.perfil-admin__card {
  padding: 2rem;
  text-align: center;
}

.perfil-admin__avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-azul-oscuro), var(--color-azul-real));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.perfil-admin__avatar span {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
}

.perfil-admin__nombre {
  font-family: var(--font-heading);
  font-size: 1.375rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.perfil-admin__correo {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.perfil-admin__section {
  padding: 1.5rem;
}

.perfil-admin__section-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-azul-real);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border);
}

.perfil-admin__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.perfil-admin__field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.perfil-admin__label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-bronce);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.perfil-admin__value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-primary);
}

.perfil-admin__value--gold {
  color: var(--color-azul-real);
  font-weight: 700;
  font-size: 1.125rem;
}

.perfil-admin__calc-note {
  margin-top: 1rem;
  font-size: 0.75rem;
  color: var(--color-bronce);
  font-style: italic;
}

.perfil-admin__horarios {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.perfil-admin__horario-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.75rem 1rem;
  background: var(--color-bg-input);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.perfil-admin__horario-item--descanso {
  opacity: 0.5;
}

.perfil-admin__horario-dia {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.perfil-admin__horario-horas {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
}

.perfil-admin__horario-descanso {
  font-size: 0.75rem;
  color: var(--color-rojo-vintage);
  font-style: italic;
}

.perfil-admin__empty {
  color: var(--color-text-muted);
  font-size: 0.875rem;
  text-align: center;
  padding: 1.5rem;
}

.perfil-admin__readonly-notice {
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

.perfil-admin__readonly-notice span {
  font-size: 1.25rem;
  flex-shrink: 0;
}
</style>
