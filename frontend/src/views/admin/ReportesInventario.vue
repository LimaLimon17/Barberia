<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Reporte de Inventario</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Movimientos de stock y ganancias por productos</p>
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

    <div v-else-if="datos" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
          <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-900/50 dark:text-slate-300">
            <tr>
              <th scope="col" class="px-4 py-3">Producto</th>
              <th scope="col" class="px-4 py-3 text-center">Stock Inicial (Aprox)</th>
              <th scope="col" class="px-4 py-3 text-center">Cantidad Vendida</th>
              <th scope="col" class="px-4 py-3 text-center">Stock Final</th>
              <th scope="col" class="px-4 py-3 font-bold text-right">Ganancia Acumulada</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in datos.inventario" :key="p.id_producto" 
                :class="[p.alerta ? 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30' : 'bg-white border-b dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50', 'dark:border-slate-700 transition-colors border-b']">
              <td class="px-4 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-2">
                <span v-if="p.alerta" title="Stock Bajo (<= 5)" class="flex w-3 h-3 bg-red-500 rounded-full"></span>
                {{ p.nombre }}
              </td>
              <td class="px-4 py-4 text-center">{{ p.stock_inicial }}</td>
              <td class="px-4 py-4 text-center font-bold text-indigo-600 dark:text-indigo-400">{{ p.cantidad_vendida }}</td>
              <td class="px-4 py-4 text-center">
                <span :class="p.alerta ? 'text-red-600 dark:text-red-400 font-bold' : ''">{{ p.stock_final }}</span>
              </td>
              <td class="px-4 py-4 text-right font-bold text-emerald-600 dark:text-emerald-400">Bs. {{ formatNumber(p.ganancia_acumulada) }}</td>
            </tr>
            <tr v-if="datos.inventario.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-slate-500">No hay datos de inventario.</td>
            </tr>
          </tbody>
        </table>
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

const formatFecha = (d) => d.toISOString().split('T')[0]

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
    
    const res = await reportesService.getInventarioAdmin(params)
    datos.value = res
  } catch (error) {
    console.error('Error cargando inventario:', error)
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
    pdfGenerator.exportarInventario(datos.value, filtros.value)
  }
}

onMounted(() => {
  cargarBarberos()
  cargarReporte()
})
</script>
