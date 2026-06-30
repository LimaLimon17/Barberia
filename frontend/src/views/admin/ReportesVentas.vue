<template>
  <div class="reportes animate-fade-in">
    <div class="reportes__header">
      <h1 class="reportes__title">📈 Reporte de <span class="gold-text">Ventas</span></h1>
    </div>

    <div class="reportes__filtros glass-card">
      <div class="reportes__filtro-group">
        <label class="label">Fecha Inicio</label>
        <input type="date" class="input-field" v-model="filtroInicio" />
      </div>
      <div class="reportes__filtro-group">
        <label class="label">Fecha Fin</label>
        <input type="date" class="input-field" v-model="filtroFin" />
      </div>
      <div class="reportes__filtro-group">
        <label class="label">Barbero</label>
        <select class="input-field" v-model="filtroBarbero">
          <option value="">Todos los barberos</option>
        <option v-for="b in listaBarberos" :key="b.id_barbero" :value="b.id_barbero">
  {{ b.nombre_completo }}
</option>
        </select>
      </div>
      <div class="reportes__filtro-group">
        <label class="label">Servicio</label>
        <select class="input-field" v-model="filtroServicio">
          <option value="">Todos los servicios</option>
          <option v-for="s in listaServicios" :key="s.IdServicio" :value="s.IdServicio">
            {{ s.Nombre }}
          </option>
        </select>
      </div>
      <button class="btn-primary" @click="cargarReporte" id="btn-buscar-ventas">🔍 Buscar</button>
      <button class="btn-secondary" @click="limpiarFiltros" v-if="filtroBarbero || filtroServicio">🧹 Limpiar Filtros</button>
    </div>

    <div v-if="cargando" class="reportes__loading glass-card"><p>Cargando reporte...</p></div>

    <template v-if="datos && !cargando">
      <!-- Totales consolidados (HU-15 Esc.3) -->
      <div class="reportes__resumen">
        <div class="reportes__stat glass-card">
          <p class="reportes__stat-label">Ingreso Total</p>
          <p class="reportes__stat-value reportes__stat-value--big">Bs. {{ parseFloat(datos.resumen.ingreso_total).toFixed(2) }}</p>
        </div>
        <div class="reportes__stat glass-card">
          <p class="reportes__stat-label">Servicios Atendidos</p>
          <p class="reportes__stat-value">{{ datos.resumen.cantidad_servicios }}</p>
        </div>
        <div class="reportes__stat glass-card">
          <p class="reportes__stat-label">Productos Vendidos</p>
          <p class="reportes__stat-value">{{ datos.resumen.cantidad_productos }}</p>
        </div>
      </div>

      <!-- Tabla de Transacciones (HU-15 Esc.1, 4) -->
      <div class="reportes__section glass-card">
        <h2 class="reportes__section-title">Transacciones del Periodo</h2>
        <div class="reportes__table-wrapper" v-if="datos.transacciones.length > 0">
          <table class="reportes__table">
            <thead>
              <tr>
                <th>Ref</th>
                <th>Fecha</th>
                <th>Barbero</th>
                <th>Servicios</th>
                <th>Productos</th>
                <th>Pago</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, i) in datos.transacciones" :key="i">
                <td class="reportes__td-ref">{{ t.referencia }}</td>
                <td>{{ t.fecha }}</td>
                <td>{{ t.barbero }}</td>
                <td>{{ t.servicios || '-' }}</td>
                <td>{{ t.productos || '-' }}</td>
                <td>{{ t.metodos_pago || '-' }}</td>
                <td class="reportes__td-total">Bs. {{ parseFloat(t.monto_total).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="reportes__empty">No hay transacciones en este periodo.</p>
      </div>

      <!-- Exportar (HU-15 Esc.5) -->
      <div class="reportes__actions">
        <button class="btn-primary" @click="exportarPDF" id="btn-exportar-ventas">📄 Exportar a PDF (Vista Previa)</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportesService } from '../../services/reportesService'
import { pdfGenerator } from '../../utils/pdfGenerator'
import api from '../../services/api.js'

const hoy = new Date()
const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)

const filtroInicio = ref(inicioMes.toISOString().split('T')[0])
const filtroFin = ref(hoy.toISOString().split('T')[0])
const filtroBarbero = ref('')
const filtroServicio = ref('')
const datos = ref(null)
const cargando = ref(false)
const listaBarberos = ref([])
const listaServicios = ref([])

const cargarReporte = async () => {
  try {
    cargando.value = true
    const params = { fecha_inicio: filtroInicio.value, fecha_fin: filtroFin.value }
    if (filtroBarbero.value) params.id_barbero = filtroBarbero.value
    if (filtroServicio.value) params.id_servicio = filtroServicio.value
    datos.value = await reportesService.getVentasAdmin(params)
  } catch (error) {
    console.error('Error cargando ventas:', error)
  } finally {
    cargando.value = false
  }
}

const cargarServicios = async () => {
  try {
    const response = await api.get('/admin/servicios')
    listaServicios.value = response.data.data
  } catch (error) {
    console.error('Error cargando servicios:', error)
  }
}

const cargarBarberos = async () => {
  try {
    const response = await api.get('/admin/barberos')
    listaBarberos.value = response.data.barberos
  } catch (error) {
    console.error('Error cargando barberos:', error)
  }
}



const limpiarFiltros = () => {
  filtroBarbero.value = ''
  filtroServicio.value = ''
  filtroInicio.value = inicioMes.toISOString().split('T')[0]
  filtroFin.value = hoy.toISOString().split('T')[0]
  cargarReporte()
}

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarReporteVentas(datos.value, { inicio: filtroInicio.value, fin: filtroFin.value })
  }
}

onMounted(() => { cargarReporte(); cargarBarberos(); cargarServicios(); })
</script>

<style scoped>
.reportes { max-width: 1200px; }
.reportes__header { margin-bottom: 1.5rem; }
.reportes__title { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; }
.reportes__filtros { padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
.reportes__filtro-group { display: flex; flex-direction: column; min-width: 160px; }
.reportes__loading { padding: 2rem; text-align: center; color: var(--color-text-muted); }
.reportes__resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.reportes__stat { padding: 1.25rem; text-align: center; }
.reportes__stat-label { font-size: 0.8125rem; color: var(--color-text-muted); margin-bottom: 0.25rem; }
.reportes__stat-value { font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; color: var(--color-azul-oscuro); }
.reportes__stat-value--big { color: var(--color-success); font-size: 1.75rem; }
.reportes__section { padding: 1.5rem; margin-bottom: 1.5rem; }
.reportes__section-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
.reportes__table-wrapper { overflow-x: auto; }
.reportes__table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.reportes__table th { text-align: left; padding: 0.75rem 0.75rem; background: var(--color-azul-oscuro); color: #fff; font-family: var(--font-heading); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
.reportes__table th:first-child { border-radius: var(--radius-sm) 0 0 0; }
.reportes__table th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
.reportes__table td { padding: 0.625rem 0.75rem; border-bottom: 1px solid var(--color-border-light); }
.reportes__table tbody tr:hover { background: var(--color-bg-hover); }
.reportes__td-ref { font-weight: 600; color: var(--color-azul-real); }
.reportes__td-total { font-weight: 700; color: var(--color-success); }
.reportes__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); }
.reportes__actions { display: flex; justify-content: flex-end; margin-top: 1rem; }
</style>
