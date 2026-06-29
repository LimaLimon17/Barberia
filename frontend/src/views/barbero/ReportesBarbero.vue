<template>
  <div class="space-y-6 max-w-[1100px] mx-auto p-4 sm:p-0">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Mis Reportes y Comisiones</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Revisa tus citas completadas, productos vendidos y ganancias.
          </p>
        </div>
        
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
          <select v-model="filtros.periodo" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 transition-colors">
            <option value="diario">Diario</option>
            <option value="semanal">Semanal</option>
          </select>

          <input type="date" v-model="filtros.fecha" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 transition-colors">
          
          <button @click="cargarReporte" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            Filtrar
          </button>
          
          <button @click="exportarPDF" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 bg-red-600 border border-transparent rounded-lg hover:bg-red-700 shadow-lg shadow-red-500/30">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Exportar a PDF
          </button>
        </div>
      </div>
    </div>

    <div v-if="cargando" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="datos" class="space-y-6">
      
      <!-- Tarjetas de Resumen -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-1">Ingresos Totales Brutos</p>
          <h3 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Bs. {{ formatNumber(datos.ingresos_totales) }}</h3>
          <p class="text-xs text-slate-400 mt-2">Suma de todos tus servicios y ventas.</p>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg shadow-indigo-500/30">
          <p class="text-indigo-100 text-sm font-medium mb-1">Mi Comisión Total</p>
          <h3 class="text-4xl font-bold tracking-tight">Bs. {{ formatNumber(datos.comision_calculada) }}</h3>
          <div class="mt-4 flex flex-col gap-1 text-xs text-indigo-100">
            <div class="flex justify-between"><span>Por Servicios:</span> <span>Bs. {{ formatNumber(datos.desglose_ganancias.servicios) }}</span></div>
            <div class="flex justify-between"><span>Por Productos:</span> <span>Bs. {{ formatNumber(datos.desglose_ganancias.productos) }}</span></div>
            <div class="flex justify-between"><span>Por Ausentes:</span> <span>Bs. {{ formatNumber(datos.desglose_ganancias.ausentes) }}</span></div>
          </div>
        </div>
      </div>

      <!-- Tabla Detalle -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white">Detalle de Transacciones</h3>
          <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-full">
            {{ datos.detalle_transacciones.length }} registros
          </span>
        </div>
        <div class="overflow-x-auto max-h-[500px]">
          <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
            <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-900/50 dark:text-slate-300 sticky top-0 shadow-sm z-10">
              <tr>
                <th scope="col" class="px-6 py-4 font-semibold">Fecha y Hora</th>
                <th scope="col" class="px-6 py-4 font-semibold">Cliente</th>
                <th scope="col" class="px-6 py-4 font-semibold">Detalle</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right">Monto Bruto</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right">Mi Comisión</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, i) in datos.detalle_transacciones" :key="i" class="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ formatFechaHora(t.Fecha) }}</td>
                <td class="px-6 py-4">{{ t.Cliente }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex max-w-[200px] truncate" :title="t.Detalle">{{ t.Detalle }}</span>
                </td>
                <td class="px-6 py-4 text-right">Bs. {{ formatNumber(t.MontoTotal) }}</td>
                <td class="px-6 py-4 text-right font-bold text-indigo-600 dark:text-indigo-400">Bs. {{ formatNumber(t.Comision) }}</td>
              </tr>
              <tr v-if="datos.detalle_transacciones.length === 0">
                <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                  No hay transacciones registradas en el periodo seleccionado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportesService } from '../../services/reportesService'
import { pdfGenerator } from '../../utils/pdfGenerator'

const hoy = new Date()

const formatFechaInput = (d) => d.toISOString().split('T')[0]
const formatFechaHora = (str) => {
  const d = new Date(str)
  return d.toLocaleString('es-BO', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const filtros = ref({
  periodo: 'semanal',
  fecha: formatFechaInput(hoy)
})

const datos = ref(null)
const cargando = ref(true)

const cargarReporte = async () => {
  cargando.value = true
  try {
    const params = { periodo: filtros.value.periodo, fecha: filtros.value.fecha }
    const res = await reportesService.getReporteBarbero(params)
    datos.value = res
  } catch (error) {
    console.error('Error cargando reporte de barbero:', error)
  } finally {
    cargando.value = false
  }
}

const formatNumber = (num) => parseFloat(num).toFixed(2)

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarReporteBarbero(datos.value)
  }
}

onMounted(() => {
  cargarReporte()
})
</script>
