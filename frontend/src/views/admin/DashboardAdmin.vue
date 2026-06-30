<template>
  <div class="dashboard animate-fade-in">
    <div class="dashboard__welcome">
      <h1 class="dashboard__greeting">
        Panel de <span class="gold-text">Administración</span> ⚙️
      </h1>
      <p class="dashboard__date">{{ fechaHoy }}</p>
    </div>

    <!-- Tarjetas de Acceso Rápido (HU-14 Escenarios 3-9) -->
    <div class="dashboard__cards">
      <router-link to="/admin/barberos" class="dashboard__card glass-card dashboard__card--link" id="btn-admin-barberos">
        <div class="dashboard__card-icon">✂️</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Barberos</h3>
          <p class="dashboard__card-desc">Gestionar equipo de barberos</p>
        </div>
        <span class="dashboard__card-arrow">→</span>
      </router-link>

      <router-link to="/admin/finanzas" class="dashboard__card glass-card dashboard__card--link" id="btn-admin-finanzas">
        <div class="dashboard__card-icon">💰</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Finanzas</h3>
          <p class="dashboard__card-desc">Rendimiento financiero semanal</p>
        </div>
        <span class="dashboard__card-arrow">→</span>
      </router-link>

      <router-link to="/admin/reportes/ventas" class="dashboard__card glass-card dashboard__card--link" id="btn-admin-ventas">
        <div class="dashboard__card-icon">📈</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Ventas</h3>
          <p class="dashboard__card-desc">Reportes de ventas consolidadas</p>
        </div>
        <span class="dashboard__card-arrow">→</span>
      </router-link>

      <router-link to="/admin/reportes/inventario" class="dashboard__card glass-card dashboard__card--link" id="btn-admin-inventario">
        <div class="dashboard__card-icon">📦</div>
        <div class="dashboard__card-info">
          <h3 class="dashboard__card-title">Inventario</h3>
          <p class="dashboard__card-desc">Control de productos y stock</p>
        </div>
        <span class="dashboard__card-arrow">→</span>
      </router-link>
    </div>

    <!-- Tabla de Barberos Consolidada (HU-14 Escenario 1) -->
    <div class="dashboard__section glass-card" v-if="!cargando">
      <h2 class="dashboard__section-title">👥 Rendimiento de Barberos (Semana Actual)</h2>
      <div v-if="barberos.length > 0" class="dashboard__table-wrapper">
        <table class="dashboard__table">
          <thead>
            <tr>
              <th>Barbero</th>
              <th>Citas Hoy</th>
              <th>Ganancia Semanal (Aprox.)</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in barberos" :key="b.id">
              <td class="dashboard__td-name">{{ b.nombre }}</td>
              <td>{{ b.citas_hoy }}</td>
              <td class="dashboard__td-money">Bs. {{ parseFloat(b.ganancia_semana).toFixed(2) }}</td>
              <td><span class="badge-active">{{ b.estado }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="dashboard__empty">No hay barberos activos registrados.</p>
    </div>

    <!-- Citas del Día (HU-14 Escenario 2) -->
    <div class="dashboard__section glass-card" v-if="!cargando" style="margin-top: 1.5rem;">
      <h2 class="dashboard__section-title">🗓️ Citas de Hoy</h2>
      <div v-if="citasHoy.length > 0" class="dashboard__table-wrapper">
        <table class="dashboard__table">
          <thead>
            <tr>
              <th>Hora</th>
              <th>Barbero</th>
              <th>Cliente</th>
              <th>Servicios</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in citasHoy" :key="c.id">
              <td class="dashboard__td-time">{{ c.hora }}</td>
              <td>{{ c.barbero }}</td>
              <td>{{ c.cliente }}</td>
              <td>{{ c.servicios }}</td>
              <td>
                <span :class="c.estado === 'Completada' ? 'badge-active' : 'badge-inactive'">
                  {{ c.estado }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="dashboard__empty">No hay citas programadas para hoy.</p>
    </div>

    <div v-if="cargando" class="dashboard__section glass-card">
      <p class="dashboard__empty">Cargando datos del panel...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { dashboardService } from '../../services/dashboardService'

const fechaHoy = computed(() => {
  return new Date().toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
})

const barberos = ref([])
const citasHoy = ref([])
const cargando = ref(true)

const cargarDashboard = async () => {
  try {
    cargando.value = true
    const data = await dashboardService.getDashboardAdmin()
    barberos.value = data.barberos || []
    citasHoy.value = data.citas_hoy || []
  } catch (error) {
    console.error('Error cargando dashboard:', error)
  } finally {
    cargando.value = false
  }
}

onMounted(cargarDashboard)
</script>

<style scoped>
.dashboard { max-width: 1200px; }
.dashboard__welcome { margin-bottom: 2rem; }
.dashboard__greeting { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
.dashboard__date { font-size: 0.875rem; color: var(--color-text-secondary); text-transform: capitalize; }
.dashboard__cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.dashboard__card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: inherit; }
.dashboard__card--link { position: relative; border: 2px solid transparent; }
.dashboard__card--link:hover { border-color: var(--color-azul-real); }
.dashboard__card:hover { background: var(--color-bg-hover); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13, 27, 42, 0.1); }
.dashboard__card-icon { font-size: 2rem; }
.dashboard__card-title { font-family: var(--font-heading); font-size: 1rem; font-weight: 600; color: var(--color-text-primary); margin-bottom: 0.125rem; }
.dashboard__card-desc { font-size: 0.8125rem; color: var(--color-text-secondary); }
.dashboard__card-arrow { margin-left: auto; font-size: 1.25rem; color: var(--color-azul-real); font-weight: 700; transition: transform 0.2s ease; }
.dashboard__card--link:hover .dashboard__card-arrow { transform: translateX(4px); }
.dashboard__section { padding: 1.5rem; }
.dashboard__section-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
.dashboard__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); font-size: 0.875rem; }
.dashboard__table-wrapper { overflow-x: auto; }
.dashboard__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.dashboard__table th { text-align: left; padding: 0.75rem 1rem; background: var(--color-azul-oscuro); color: #fff; font-family: var(--font-heading); font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.03em; }
.dashboard__table th:first-child { border-radius: var(--radius-sm) 0 0 0; }
.dashboard__table th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
.dashboard__table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border-light); }
.dashboard__table tbody tr:hover { background: var(--color-bg-hover); }
.dashboard__td-name { font-weight: 600; color: var(--color-azul-oscuro); }
.dashboard__td-money { font-weight: 700; color: var(--color-success); }
.dashboard__td-time { font-weight: 600; color: var(--color-azul-real); }
</style>