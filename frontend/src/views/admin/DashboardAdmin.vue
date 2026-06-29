<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Panel Principal de Administración</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 capitalize">{{ fechaHoy }}</p>
        </div>
      </router-link>

      <div class="dashboard__card glass-card">
        <div class="dashboard__card-icon">📦</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Inventario</h3>
          <p class="dashboard__card-desc">Control de productos</p>
        </div>
      </div>

      <div class="dashboard__card glass-card">
        <div class="dashboard__card-icon">💰</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Comisiones</h3>
          <p class="dashboard__card-desc">Reportes de ganancias</p>
        </div>
      </div>

      <div class="dashboard__card glass-card">
        <div class="dashboard__card-icon">📊</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Reportes</h3>
          <p class="dashboard__card-desc">Estadísticas del negocio</p>
        </div>
      </div>
    </div>

    <div class="dashboard__section glass-card">
      <h2 class="dashboard__section-title">📋 Resumen del día</h2>
      <p class="dashboard__empty">Panel en desarrollo. Usa el menú lateral para navegar.</p>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'

const fechaHoy = computed(() => {
  return new Date().toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
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
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
}

.dashboard__card:hover {
  background: var(--color-bg-hover);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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
