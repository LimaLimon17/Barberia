<template>
  <div class="dashboard animate-fade-in">
    <div class="dashboard__welcome">
      <h1 class="dashboard__greeting">
        Hola,
        <span class="gold-text">
          {{ authStore.usuario?.nombre1 }}
        </span>
        👋
      </h1>

      <p class="dashboard__date">
        {{ fechaHoy }}
      </p>
    </div>

    <div class="dashboard__cards">

      <!-- Mi Agenda -->
      <div class="dashboard__card glass-card">
        <div class="dashboard__card-icon">📅</div>

        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">
            Mi Agenda
          </h3>

          <p class="dashboard__card-desc">
            Consulta tus citas del día
          </p>
        </div>
      </div>

      <!-- Mi Perfil -->
      <router-link
        to="/barbero/perfil"
        class="dashboard__card glass-card dashboard__card--link"
        id="btn-ver-perfil"
      >
        <div class="dashboard__card-icon">👤</div>

        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">
            Mi Perfil
          </h3>

          <p class="dashboard__card-desc">
            Ver tu información personal y antigüedad
          </p>
        </div>

        <span class="dashboard__card-arrow">
          →
        </span>
      </router-link>

      <!-- Comisiones -->
      <router-link
        to="/barbero/reportes"
        class="dashboard__card glass-card dashboard__card--link"
        id="btn-ver-comisiones"
      >
        <div class="dashboard__card-icon">💰</div>

        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">
            Comisiones y Reportes
          </h3>

          <p class="dashboard__card-desc">
            Revisa tus ganancias y citas
          </p>
        </div>

        <span class="dashboard__card-arrow">
          →
        </span>
      </router-link>

    </div>

    <div class="dashboard__section glass-card">
      <h2 class="dashboard__section-title">
        🗓️ Citas de hoy
      </h2>

      <p class="dashboard__empty">
        No hay citas programadas para hoy.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth.js'

const authStore = useAuthStore()

const fechaHoy = computed(() => {
  return new Date().toLocaleDateString('es-BO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})
</script>

<style scoped>
.dashboard {
  max-width: 1100px;
}

.dashboard__welcome {
  margin-bottom: 2rem;
}

.dashboard__greeting {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.dashboard__date {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  text-transform: capitalize;
}

.dashboard__cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.dashboard__card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  color: inherit;
}

.dashboard__card--link {
  position: relative;
  border: 2px solid transparent;
}

.dashboard__card--link:hover {
  border-color: var(--color-azul-real);
}

.dashboard__card:hover {
  background: var(--color-bg-hover);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(13, 27, 42, 0.1);
}

.dashboard__card-icon {
  font-size: 2rem;
}

.dashboard__card-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 0.125rem;
}

.dashboard__card-desc {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
}

.dashboard__card-arrow {
  margin-left: auto;
  font-size: 1.25rem;
  color: var(--color-azul-real);
  font-weight: 700;
  transition: transform 0.2s ease;
}

.dashboard__card--link:hover .dashboard__card-arrow {
  transform: translateX(4px);
}

.dashboard__section {
  padding: 1.5rem;
}

.dashboard__section-title {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.dashboard__empty {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
</style>