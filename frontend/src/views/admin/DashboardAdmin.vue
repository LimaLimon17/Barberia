<template>
  <div class="dashboard animate-fade-in">
    <div class="dashboard__welcome">
      <h1 class="dashboard__greeting">
        Panel de <span class="gold-text">Administración</span> ⚙️
      </h1>
      <p class="dashboard__date">{{ fechaHoy }}</p>
    </div>

  <div class="dashboard__cards">
  <router-link to="/admin/barberos" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">✂️</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Barberos</h3>
      <p class="dashboard__card-desc">Gestionar equipo de barberos</p>
    </div>
  </router-link>

  <router-link to="/admin/horarios" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">🗓️</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Horarios</h3>
      <p class="dashboard__card-desc">Asignación de descansos FIFO</p>
    </div>
  </router-link>

  <router-link to="/admin/servicios" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">🗂️</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Catálogo de Servicios</h3>
      <p class="dashboard__card-desc">Categorías y servicios disponibles para reserva</p>
    </div>
  </router-link>

  <router-link to="/admin/productos/porcentajes" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">🏷️</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Porcentajes de Productos</h3>
      <p class="dashboard__card-desc">Precio de venta y comisión por producto</p>
    </div>
  </router-link>

  <router-link to="/admin/productos" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">📦</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Inventario</h3>
      <p class="dashboard__card-desc">Control de productos y stock</p>
    </div>
  </router-link>

  <router-link to="/admin/finanzas" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">💰</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Finanzas</h3>
      <p class="dashboard__card-desc">Ingresos, fondos y comisiones a pagar</p>
    </div>
  </router-link>

  <router-link to="/admin/reportes/ventas" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">📈</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Reporte de Ventas</h3>
      <p class="dashboard__card-desc">Transacciones filtradas por barbero/servicio</p>
    </div>
  </router-link>

  <router-link to="/admin/reportes/inventario" class="dashboard__card glass-card" style="text-decoration:none;">
    <div class="dashboard__card-icon">📋</div>
    <div class="dashboard__card-info">
      <h3 class="dashboard__card-title">Reporte de Inventario</h3>
      <p class="dashboard__card-desc">Stock, ventas y alertas por producto</p>
    </div>
  </router-link>
</div>
</div>
</template>

<script setup>
import { computed } from 'vue'

const fechaHoy = computed(() => {
  return new Date().toLocaleDateString('es-BO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
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