<template>
  <div class="reportes animate-fade-in">
    <div class="reportes__header">
      <h1 class="reportes__title">💰 Mis <span class="gold-text">Comisiones y Reportes</span></h1>
    </div>

    <!-- Filtros (HU-17 Esc.5) -->
    <div class="reportes__filtros glass-card">
      <div class="reportes__filtro-group">
        <label class="label">Periodo</label>
        <select class="input-field" v-model="filtroPeriodo" @change="cargarReporte">
          <option value="semana">Semanal (Lunes - Domingo)</option>
          <option value="dia">Diario (Hoy)</option>
        </select>
      </div>
    </div>

    <div v-if="cargando" class="reportes__loading glass-card"><p>Cargando tu reporte...</p></div>

    <template v-if="datos && !cargando">
      <p class="reportes__periodo-text">Periodo: {{ datos.periodo.inicio }} al {{ datos.periodo.fin }}</p>

      <!-- Resumen de Rendimiento (HU-17 Esc.3, 4) -->
      <div class="reportes__resumen">
        <div class="reportes__stat glass-card">
          <span class="reportes__stat-icon">📊</span>
          <div>
            <p class="reportes__stat-label">Ingresos Totales Brutos</p>
            <p class="reportes__stat-value">Bs. {{ parseFloat(datos.ingresos_totales).toFixed(2) }}</p>
          </div>
        </div>
        <div class="reportes__stat glass-card reportes__stat--highlight">
          <span class="reportes__stat-icon">💎</span>
          <div>
            <p class="reportes__stat-label">Comisión Total a Cobrar</p>
            <p class="reportes__stat-value reportes__stat-value--big">Bs. {{ parseFloat(datos.comision_calculada).toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <!-- Desglose de Ganancias (HU-10 / RF18) -->
      <div class="reportes__desglose glass-card">
        <h2 class="reportes__section-title">📋 Desglose de Comisiones</h2>
        <div class="reportes__desglose-grid">
          <div class="reportes__desglose-item">
            <span>✂️ Servicios (50%)</span>
            <strong>Bs. {{ parseFloat(datos.desglose_ganancias.servicios).toFixed(2) }}</strong>
          </div>
          <div class="reportes__desglose-item">
            <span>🛍️ Productos</span>
            <strong>Bs. {{ parseFloat(datos.desglose_ganancias.productos).toFixed(2) }}</strong>
          </div>
          <div class="reportes__desglose-item">
            <span>🚫 Ausentes (50%)</span>
            <strong>Bs. {{ parseFloat(datos.desglose_ganancias.ausentes).toFixed(2) }}</strong>
          </div>
        </div>
      </div>

      <!-- Detalle Cronológico (HU-17 Esc.1, 2 / RF17) -->
      <div class="reportes__section glass-card">
        <h2 class="reportes__section-title">🗓️ Detalle Cronológico</h2>
        <div class="reportes__table-wrapper" v-if="datos.detalle_transacciones.length > 0">
          <table class="reportes__table">
            <thead>
              <tr>
                <th>Fecha y Hora</th>
                <th>Cliente</th>
                <th>Detalle</th>
                <th>Monto Bruto</th>
                <th>Comisión Ganada</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, i) in datos.detalle_transacciones" :key="i">
                <td class="reportes__td-fecha">{{ t.Fecha }}</td>
                <td>{{ t.Cliente }}</td>
                <td>{{ t.Detalle }}</td>
                <td>Bs. {{ parseFloat(t.MontoTotal).toFixed(2) }}</td>
                <td class="reportes__td-comision">Bs. {{ parseFloat(t.Comision).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="reportes__empty">No hay transacciones en este periodo.</p>
      </div>

      <!-- Exportar (HU-17 Esc.6 / HU-10 Esc.6) -->
      <div class="reportes__actions">
        <button class="btn-primary" @click="exportarPDF" id="btn-exportar-barbero">📄 Exportar a PDF (Vista Previa)</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportesService } from '../../services/reportesService'
import { pdfGenerator } from '../../utils/pdfGenerator'

const filtroPeriodo = ref('semana')
const datos = ref(null)
const cargando = ref(false)

const cargarReporte = async () => {
  try {
    cargando.value = true
    datos.value = await reportesService.getReporteBarbero({ periodo: filtroPeriodo.value })
  } catch (error) {
    console.error('Error cargando reporte:', error)
  } finally {
    cargando.value = false
  }
}

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarReporteBarbero(datos.value)
  }
}

onMounted(cargarReporte)
</script>

<style scoped>
.reportes { max-width: 1100px; }
.reportes__header { margin-bottom: 1.5rem; }
.reportes__title { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; }
.reportes__filtros { padding: 1.25rem 1.5rem; margin-bottom: 1rem; display: flex; align-items: flex-end; gap: 1rem; }
.reportes__filtro-group { display: flex; flex-direction: column; min-width: 250px; }
.reportes__loading { padding: 2rem; text-align: center; color: var(--color-text-muted); }
.reportes__periodo-text { font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1rem; }
.reportes__resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.reportes__stat { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; }
.reportes__stat--highlight { border: 2px solid var(--color-azul-real); }
.reportes__stat-icon { font-size: 2rem; }
.reportes__stat-label { font-size: 0.8125rem; color: var(--color-text-muted); margin-bottom: 0.125rem; }
.reportes__stat-value { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; color: var(--color-azul-oscuro); }
.reportes__stat-value--big { font-size: 1.5rem; color: var(--color-success); }
.reportes__desglose { padding: 1.5rem; margin-bottom: 1.5rem; }
.reportes__section-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
.reportes__desglose-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; }
.reportes__desglose-item { display: flex; justify-content: space-between; padding: 0.75rem 1rem; background: var(--color-bg-primary); border-radius: var(--radius-sm); font-size: 0.9375rem; }
.reportes__section { padding: 1.5rem; margin-bottom: 1.5rem; }
.reportes__table-wrapper { overflow-x: auto; }
.reportes__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.reportes__table th { text-align: left; padding: 0.75rem 1rem; background: var(--color-azul-oscuro); color: #fff; font-family: var(--font-heading); font-weight: 600; text-transform: uppercase; font-size: 0.8125rem; }
.reportes__table th:first-child { border-radius: var(--radius-sm) 0 0 0; }
.reportes__table th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
.reportes__table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border-light); }
.reportes__table tbody tr:hover { background: var(--color-bg-hover); }
.reportes__td-fecha { font-weight: 600; color: var(--color-azul-real); }
.reportes__td-comision { font-weight: 700; color: var(--color-success); }
.reportes__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); font-size: 0.875rem; }
.reportes__actions { display: flex; justify-content: flex-end; margin-top: 1rem; }
</style>
