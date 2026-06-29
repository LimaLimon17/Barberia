<template>
  <div class="space-y-6">
    <!-- Encabezado y Filtros -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Panel de Finanzas</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Rendimiento consolidado de la semana actual
            <span v-if="datos" class="font-medium text-indigo-600 dark:text-indigo-400">
              ({{ datos.periodo.inicio }} al {{ datos.periodo.fin }})
            </span>
          </p>
        </div>
        
        <div class="flex flex-wrap gap-3">
          <!-- Filtro Barbero -->
          <select 
            v-model="filtroBarbero" 
            @change="cargarFinanzas"
            class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-colors"
          >
            <option value="">Todos los Barberos</option>
            <option v-for="b in listaBarberos" :key="b.IdBarbero" :value="b.IdBarbero">
              {{ b.usuario?.Nombre1 }} {{ b.usuario?.Apellido1 }}
            </option>
          </select>
          
          <button 
            @click="exportarPDF"
            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-lg shadow-red-500/30"
          >
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
      <!-- Tarjetas Generales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ingresos Totales -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg shadow-indigo-500/30 relative overflow-hidden group">
          <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
          </div>
          <p class="text-indigo-100 text-sm font-medium mb-1">Ingresos Brutos Totales</p>
          <h3 class="text-3xl font-bold tracking-tight">Bs. {{ formatNumber(datos.ingresos_totales) }}</h3>
          <div class="mt-4 flex gap-4 text-xs font-medium text-indigo-100">
            <div>Serv: {{ formatNumber(datos.ingresos_servicios) }}</div>
            <div>Prod: {{ formatNumber(datos.ingresos_ventas) }}</div>
          </div>
        </div>
        
        <!-- Fondos Barberia -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
          <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-1">Fondo Barbería</p>
          <h3 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Bs. {{ formatNumber(datos.fondos_barberia.total) }}</h3>
          <div class="mt-4 flex flex-col gap-1 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex justify-between"><span>Servicios (50%):</span> <span class="font-medium">Bs. {{ formatNumber(datos.fondos_barberia.servicios) }}</span></div>
            <div class="flex justify-between"><span>Productos:</span> <span class="font-medium">Bs. {{ formatNumber(datos.fondos_barberia.productos) }}</span></div>
            <div class="flex justify-between"><span>Ausentes (50%):</span> <span class="font-medium">Bs. {{ formatNumber(datos.fondos_barberia.ausentes) }}</span></div>
          </div>
        </div>

        <!-- Comisiones a Pagar -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/30 relative overflow-hidden group lg:col-span-2">
          <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
          </div>
          <p class="text-emerald-100 text-sm font-medium mb-1">Total Comisiones a Pagar (Semana)</p>
          <h3 class="text-4xl font-bold tracking-tight">Bs. {{ formatNumber(datos.comisiones_a_pagar) }}</h3>
          <p class="mt-2 text-sm text-emerald-100">Para {{ datos.desglose_barberos.length }} barbero(s) activos</p>
        </div>
      </div>

      <!-- Tabla de Desglose por Barbero -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white">Desglose de Ganancias por Barbero</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
            <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-900/50 dark:text-slate-300">
              <tr>
                <th scope="col" class="px-6 py-4 rounded-tl-lg font-semibold">Barbero</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right">Comisión Servicios</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right">Comisión Productos</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right">Ausentes (50%)</th>
                <th scope="col" class="px-6 py-4 font-semibold text-right rounded-tr-lg">Total a Pagar</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in datos.desglose_barberos" :key="b.id" class="bg-white border-b dark:bg-slate-800 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    {{ b.nombre.charAt(0) }}
                  </div>
                  {{ b.nombre }}
                </td>
                <td class="px-6 py-4 text-right">Bs. {{ formatNumber(b.servicios) }}</td>
                <td class="px-6 py-4 text-right">Bs. {{ formatNumber(b.productos) }}</td>
                <td class="px-6 py-4 text-right">Bs. {{ formatNumber(b.ausentes) }}</td>
                <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">Bs. {{ formatNumber(b.total) }}</td>
              </tr>
              <tr v-if="datos.desglose_barberos.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                  No hay transacciones registradas para la semana seleccionada.
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
import { finanzasService } from '../../services/finanzasService'
import { barberoService } from '../../services/barberoService'
import { pdfGenerator } from '../../utils/pdfGenerator'

const datos = ref(null)
const cargando = ref(true)
const listaBarberos = ref([])
const filtroBarbero = ref('')

const cargarFinanzas = async () => {
  cargando.value = true
  try {
    const res = await finanzasService.getFinanzas(filtroBarbero.value)
    datos.value = res
  } catch (error) {
    console.error('Error cargando finanzas:', error)
  } finally {
    cargando.value = false
  }
}

const cargarBarberos = async () => {
  try {
    const res = await barberoService.getBarberos()
    listaBarberos.value = res.data || res // asumiendo respuesta estándar
  } catch (error) {
    console.error('Error cargando barberos:', error)
  }
}

const formatNumber = (num) => {
  return parseFloat(num).toFixed(2)
}

const exportarPDF = () => {
  if (datos.value) {
    pdfGenerator.exportarFinanzas(datos.value)
  }
}

onMounted(() => {
  cargarBarberos()
  cargarFinanzas()
})
</script>
