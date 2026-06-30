<template>
  <div class="finanzas animate-fade-in">
    <div class="finanzas__header">
      <h1 class="finanzas__title">💰 Panel de <span class="gold-text">Finanzas</span></h1>
      <p class="finanzas__subtitle" v-if="datos">Semana: {{ datos.periodo.inicio }} al {{ datos.periodo.fin }}</p>
    </div>

    <!-- Filtro por Barbero (HU-16 Esc.7) -->
    <div class="finanzas__filtro glass-card">
      <label class="label" for="select-barbero">Filtrar por Barbero</label>
      <select id="select-barbero" class="input-field" v-model="filtroBarbero" @change="cargarFinanzas">
        <option value="">Todos los barberos</option>
        <option v-for="b in listaBarberos" :key="b.IdBarbero" :value="b.IdBarbero">
          {{ b.usuario.Nombre1 }} {{ b.usuario.Apellido1 }}
        </option>
      </select>
    </div>

    <div v-if="cargando" class="finanzas__loading glass-card">
      <p>Cargando datos financieros...</p>
    </div>

    <template v-if="datos && !cargando">
      <!-- Resumen de Ingresos (HU-16 Esc. 1, 2, 3) -->
      <div class="finanzas__resumen">
        <div class="finanzas__stat-card glass-card">
          <span class="finanzas__stat-icon">🎯</span>
          <div>
            <p class="finanzas__stat-label">Ingresos por Servicios</p>
            <p class="finanzas__stat-value">Bs. {{ parseFloat(datos.ingresos_servicios).toFixed(2) }}</p>
          </div>
        </div>
        <div class="finanzas__stat-card glass-card">
          <span class="finanzas__stat-icon">🛍️</span>
          <div>
            <p class="finanzas__stat-label">Ingresos por Productos</p>
            <p class="finanzas__stat-value">Bs. {{ parseFloat(datos.ingresos_ventas).toFixed(2) }}</p>
          </div>
        </div>
        <div class="finanzas__stat-card glass-card finanzas__stat-card--highlight">
          <span class="finanzas__stat-icon">💎</span>
          <div>
            <p class="finanzas__stat-label">Total Ingresos</p>
            <p class="finanzas__stat-value finanzas__stat-value--big">Bs. {{ parseFloat(datos.ingresos_totales).toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <!-- Fondos de la Barbería (HU-16 Esc. 4) -->
      <div class="finanzas__section glass-card">
        <h2 class="finanzas__section-title">🏦 Fondos de la Barbería</h2>
        <div class="finanzas__fondos-grid">
          <div class="finanzas__fondo-item">
            <span class="finanzas__fondo-label">Retención Servicios (50%)</span>
            <span class="finanzas__fondo-value">Bs. {{ parseFloat(datos.fondos_barberia.servicios).toFixed(2) }}</span>
          </div>
          <div class="finanzas__fondo-item">
            <span class="finanzas__fondo-label">Retención Productos</span>
            <span class="finanzas__fondo-value">Bs. {{ parseFloat(datos.fondos_barberia.productos).toFixed(2) }}</span>
          </div>
          <div class="finanzas__fondo-item">
            <span class="finanzas__fondo-label">Ausentes (50%)</span>
            <span class="finanzas__fondo-value">Bs. {{ parseFloat(datos.fondos_barberia.ausentes).toFixed(2) }}</span>
          </div>
          <div class="finanzas__fondo-item finanzas__fondo-item--total">
            <span class="finanzas__fondo-label">Fondo Total</span>
            <span class="finanzas__fondo-value">Bs. {{ parseFloat(datos.fondos_barberia.total).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Comisiones a Pagar (HU-16 Esc. 5) -->
      <div class="finanzas__section glass-card">
        <h2 class="finanzas__section-title">💸 Comisiones a Pagar: <span class="finanzas__comision-total">Bs. {{ parseFloat(datos.comisiones_a_pagar).toFixed(2) }}</span></h2>
      </div>

      <!-- Desglose por Barbero (HU-16 Esc. 6) -->
      <div class="finanzas__section glass-card">
        <h2 class="finanzas__section-title">👥 Desglose por Barbero</h2>
        <div class="finanzas__table-wrapper" v-if="datos.desglose_barberos.length > 0">
          <table class="finanzas__table">
            <thead>
              <tr>
                <th>Barbero</th>
                <th>Comisión Servicios</th>
                <th>Comisión Productos</th>
                <th>Comisión Ausentes</th>
                <th>Total a Pagar</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in datos.desglose_barberos" :key="b.id">
                <td class="finanzas__td-name">{{ b.nombre }}</td>
                <td>Bs. {{ parseFloat(b.servicios).toFixed(2) }}</td>
                <td>Bs. {{ parseFloat(b.productos).toFixed(2) }}</td>
                <td>Bs. {{ parseFloat(b.ausentes).toFixed(2) }}</td>
                <td class="finanzas__td-total">Bs. {{ parseFloat(b.total).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="finanzas__empty">No hay datos para mostrar.</p>
      </div>

      <!-- Botón Exportar (HU-16 Esc. 9) -->
      <div class="finanzas__actions">
        <button class="btn-primary" @click="exportarPDF" id="btn-exportar-finanzas">
          📄 Exportar a PDF (Vista Previa)
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { finanzasService } from '../../services/finanzasService'
import { pdfGenerator } from '../../utils/pdfGenerator'
import api from '../../services/api.js'

const datos = ref(null)
const cargando = ref(true)
const filtroBarbero = ref('')
const listaBarberos = ref([])

const cargarFinanzas = async () => {
  try {
    cargando.value = true
    const data = await finanzasService.getFinanzas(filtroBarbero.value || null)
    datos.value = data
  } catch (error) {
    console.error('Error cargando finanzas:', error)
  } finally {
    cargando.value = false
  }
}

const cargarBarberos = async () => {
  try {
    const response = await api.get('/admin/barberos')
    listaBarberos.value = response.data
  } catch (error) {
    console.error('Error cargando barberos:', error)
  }
}

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarFinanzas(datos.value)
  }
}

onMounted(() => {
  cargarFinanzas()
  cargarBarberos()
})
</script>

<style scoped>
.finanzas { max-width: 1200px; }
.finanzas__header { margin-bottom: 1.5rem; }
.finanzas__title { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; }
.finanzas__subtitle { font-size: 0.875rem; color: var(--color-text-muted); margin-top: 0.25rem; }
.finanzas__filtro { padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; }
.finanzas__filtro select { max-width: 300px; }
.finanzas__loading { padding: 2rem; text-align: center; color: var(--color-text-muted); }
.finanzas__resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.finanzas__stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; }
.finanzas__stat-card--highlight { border: 2px solid var(--color-azul-real); }
.finanzas__stat-icon { font-size: 2rem; }
.finanzas__stat-label { font-size: 0.8125rem; color: var(--color-text-muted); margin-bottom: 0.125rem; }
.finanzas__stat-value { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; color: var(--color-azul-oscuro); }
.finanzas__stat-value--big { font-size: 1.5rem; color: var(--color-success); }
.finanzas__section { padding: 1.5rem; margin-bottom: 1.5rem; }
.finanzas__section-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
.finanzas__fondos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; }
.finanzas__fondo-item { display: flex; justify-content: space-between; padding: 0.75rem 1rem; background: var(--color-bg-primary); border-radius: var(--radius-sm); }
.finanzas__fondo-item--total { background: var(--color-azul-oscuro); color: #fff; border-radius: var(--radius-md); }
.finanzas__fondo-item--total .finanzas__fondo-value { color: #fff; font-weight: 700; }
.finanzas__fondo-label { font-size: 0.875rem; }
.finanzas__fondo-value { font-family: var(--font-heading); font-weight: 600; color: var(--color-azul-oscuro); }
.finanzas__comision-total { color: var(--color-rojo-vintage); }
.finanzas__table-wrapper { overflow-x: auto; }
.finanzas__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.finanzas__table th { text-align: left; padding: 0.75rem 1rem; background: var(--color-azul-oscuro); color: #fff; font-family: var(--font-heading); font-weight: 600; font-size: 0.8125rem; text-transform: uppercase; }
.finanzas__table th:first-child { border-radius: var(--radius-sm) 0 0 0; }
.finanzas__table th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
.finanzas__table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border-light); }
.finanzas__table tbody tr:hover { background: var(--color-bg-hover); }
.finanzas__td-name { font-weight: 600; color: var(--color-azul-oscuro); }
.finanzas__td-total { font-weight: 700; color: var(--color-success); }
.finanzas__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); font-size: 0.875rem; }
.finanzas__actions { display: flex; justify-content: flex-end; margin-top: 1rem; }
</style>
