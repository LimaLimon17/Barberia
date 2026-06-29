<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Reporte de Ventas Consolidadas</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Historial de servicios prestados y productos vendidos</p>
        </div>
        
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
          <input type="date" v-model="filtros.inicio" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 transition-colors">
          <span class="self-center text-slate-500">-</span>
          <input type="date" v-model="filtros.fin" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 transition-colors">
          
          <select v-model="filtros.id_barbero" class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 transition-colors">
            <option value="">Todos los Barberos</option>
            <option v-for="b in listaBarberos" :key="b.IdBarbero" :value="b.IdBarbero">
              {{ b.usuario?.Nombre1 }} {{ b.usuario?.Apellido1 }}
            </option>
          </select>
          
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-indigo-500">
          <p class="text-slate-500 text-sm">Ingreso Total del Periodo</p>
          <h3 class="text-2xl font-bold mt-1 dark:text-white">Bs. {{ formatNumber(datos.resumen.ingreso_total) }}</h3>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-blue-500">
          <p class="text-slate-500 text-sm">Total Servicios Atendidos</p>
          <h3 class="text-2xl font-bold mt-1 dark:text-white">{{ datos.resumen.cantidad_servicios }}</h3>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 border-l-4 border-l-teal-500">
          <p class="text-slate-500 text-sm">Total Productos Vendidos</p>
          <h3 class="text-2xl font-bold mt-1 dark:text-white">{{ datos.resumen.cantidad_productos }}</h3>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
            <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-900/50 dark:text-slate-300">
              <tr>
                <th scope="col" class="px-4 py-3">Ref</th>
                <th scope="col" class="px-4 py-3">Fecha y Hora</th>
                <th scope="col" class="px-4 py-3">Barbero</th>
                <th scope="col" class="px-4 py-3">Servicios Prestados</th>
                <th scope="col" class="px-4 py-3">Productos Vendidos</th>
                <th scope="col" class="px-4 py-3">Pago</th>
                <th scope="col" class="px-4 py-3 font-bold text-right">Monto Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, i) in datos.transacciones" :key="i" class="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ t.referencia }}</td>
                <td class="px-4 py-3">
                  <div class="font-medium">{{ t.fecha }}</div>
                  <div class="text-xs text-slate-500">{{ t.hora }}</div>
                </td>
                <td class="px-4 py-3">{{ t.barbero }}</td>
                <td class="px-4 py-3">{{ t.servicios || '-' }}</td>
                <td class="px-4 py-3">{{ t.productos || '-' }}</td>
                <td class="px-4 py-3">
                  <span class="bg-slate-100 text-slate-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-slate-700 dark:text-slate-300">
                    {{ t.metodos_pago || 'N/A' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">Bs. {{ formatNumber(t.monto_total) }}</td>
              </tr>
              <tr v-if="datos.transacciones.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">No hay ventas en este periodo.</td>
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
import { barberoService } from '../../services/barberoService'
import { pdfGenerator } from '../../utils/pdfGenerator'

const hoy = new Date()
const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)

const formatFecha = (d) => {
  return d.toISOString().split('T')[0]
}

const filtros = ref({
  inicio: formatFecha(inicioMes),
  fin: formatFecha(hoy),
  id_barbero: ''
})

const datos = ref(null)
const cargando = ref(true)
const listaBarberos = ref([])

const cargarReporte = async () => {
  cargando.value = true
  try {
    const params = { fecha_inicio: filtros.value.inicio, fecha_fin: filtros.value.fin }
    if (filtros.value.id_barbero) params.id_barbero = filtros.value.id_barbero
    
    const res = await reportesService.getVentasAdmin(params)
    datos.value = res
  } catch (error) {
    console.error('Error cargando reporte de ventas:', error)
  } finally {
    cargando.value = false
  }
}

const cargarBarberos = async () => {
  try {
    const res = await barberoService.getBarberos()
    listaBarberos.value = res.data || res
  } catch (error) {
    console.error('Error cargando barberos:', error)
  }
}

const formatNumber = (num) => parseFloat(num).toFixed(2)

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarReporteVentas(datos.value, filtros.value)
  }
}

onMounted(() => {
  cargarBarberos()
  cargarReporte()
})
</script>
