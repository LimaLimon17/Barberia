<template>
  <div class="inventario animate-fade-in">
    <div class="inventario__header">
      <h1 class="inventario__title">📦 Reporte de <span class="gold-text">Inventario</span></h1>
    </div>

    <!-- Filtros (RF20) -->
    <div class="inventario__filtros glass-card">
      <div class="inventario__filtro-group">
        <label class="label">Fecha Inicio</label>
        <input type="date" class="input-field" v-model="filtroInicio" />
      </div>
      <div class="inventario__filtro-group">
        <label class="label">Fecha Fin</label>
        <input type="date" class="input-field" v-model="filtroFin" />
      </div>
      <button class="btn-primary" @click="cargarReporte" id="btn-buscar-inventario">🔍 Buscar</button>
    </div>

    <div v-if="cargando" class="inventario__loading glass-card"><p>Cargando inventario...</p></div>

    <template v-if="datos && !cargando">
      <div class="inventario__section glass-card">
        <h2 class="inventario__section-title">Estado del Inventario</h2>
        <div class="inventario__table-wrapper" v-if="datos.inventario.length > 0">
          <table class="inventario__table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Stock Inicial</th>
                <th>Cant. Vendida</th>
                <th>Stock Final</th>
                <th>Ganancia Acum.</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in datos.inventario" :key="item.id" :class="{ 'inventario__row--alerta': item.alerta }">
                <td class="inventario__td-name">{{ item.nombre }}</td>
                <td>{{ item.stock_inicial }}</td>
                <td>{{ item.cantidad_vendida }}</td>
                <td>{{ item.stock_final }}</td>
                <td class="inventario__td-ganancia">Bs. {{ parseFloat(item.ganancia_acumulada).toFixed(2) }}</td>
                <td>
                  <span v-if="item.alerta" class="badge-inactive">⚠️ Stock bajo</span>
                  <span v-else class="badge-active">✅ Normal</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="inventario__empty">No hay datos de inventario.</p>
      </div>

      <div class="inventario__actions">
        <button class="btn-primary" @click="exportarPDF" id="btn-exportar-inventario">📄 Exportar a PDF (Vista Previa)</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportesService } from '../../services/reportesService'
import { pdfGenerator } from '../../utils/pdfGenerator'

const hoy = new Date()
const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)

const filtroInicio = ref(inicioMes.toISOString().split('T')[0])
const filtroFin = ref(hoy.toISOString().split('T')[0])
const datos = ref(null)
const cargando = ref(false)

const cargarReporte = async () => {
  try {
    cargando.value = true
    datos.value = await reportesService.getInventarioAdmin({ inicio: filtroInicio.value, fin: filtroFin.value })
  } catch (error) {
    console.error('Error cargando inventario:', error)
  } finally {
    cargando.value = false
  }
}

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarInventario(datos.value, { inicio: filtroInicio.value, fin: filtroFin.value })
  }
}

onMounted(cargarReporte)
</script>

<style scoped>
.inventario { max-width: 1200px; }
.inventario__header { margin-bottom: 1.5rem; }
.inventario__title { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; }
.inventario__filtros { padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
.inventario__filtro-group { display: flex; flex-direction: column; min-width: 160px; }
.inventario__loading { padding: 2rem; text-align: center; color: var(--color-text-muted); }
.inventario__section { padding: 1.5rem; margin-bottom: 1.5rem; }
.inventario__section-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; }
.inventario__table-wrapper { overflow-x: auto; }
.inventario__table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.inventario__table th { text-align: left; padding: 0.75rem 1rem; background: var(--color-azul-oscuro); color: #fff; font-family: var(--font-heading); font-weight: 600; text-transform: uppercase; font-size: 0.8125rem; }
.inventario__table th:first-child { border-radius: var(--radius-sm) 0 0 0; }
.inventario__table th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
.inventario__table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border-light); }
.inventario__table tbody tr:hover { background: var(--color-bg-hover); }
.inventario__row--alerta { background: #fff5f5 !important; }
.inventario__row--alerta:hover { background: #fee2e2 !important; }
.inventario__td-name { font-weight: 600; color: var(--color-azul-oscuro); }
.inventario__td-ganancia { font-weight: 700; color: var(--color-success); }
.inventario__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); }
.inventario__actions { display: flex; justify-content: flex-end; margin-top: 1rem; }
</style>
