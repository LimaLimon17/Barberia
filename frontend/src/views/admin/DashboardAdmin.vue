<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Panel Principal de Administración</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 capitalize">{{ fechaHoy }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <router-link to="/admin/finanzas" class="px-4 py-2 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
            Finanzas
          </router-link>
          <router-link to="/admin/reportes/ventas" class="px-4 py-2 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
            Ventas
          </router-link>
          <router-link to="/admin/reportes/inventario" class="px-4 py-2 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 font-medium rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
            Inventario
          </router-link>
        </div>
      </div>
    </div>

    <div v-if="cargando" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="datos" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      
      <!-- Lista de Barberos (Consolidado Semanal) -->
      <div class="xl:col-span-2 space-y-4">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Rendimiento Semanal del Equipo</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="b in datos.barberos" :key="b.id" class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col relative overflow-hidden group hover:border-indigo-400 transition-colors">
            <div class="absolute right-0 top-0 opacity-5 transform translate-x-4 -translate-y-4 group-hover:scale-110 transition-transform duration-500">
              <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="flex justify-between items-start mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-lg">
                  {{ b.nombre.charAt(0) }}
                </div>
                <div>
                  <h3 class="font-bold text-slate-900 dark:text-white leading-tight">{{ b.nombre }}</h3>
                  <span :class="b.estado === 'Activo' ? 'text-emerald-500' : 'text-red-500'" class="text-xs font-medium">{{ b.estado }}</span>
                </div>
              </div>
            </div>
            
            <div class="mt-auto grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
              <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ganancia Semanal</p>
                <p class="font-bold text-lg text-slate-900 dark:text-white">Bs. {{ parseFloat(b.ganancia_semanal).toFixed(2) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Citas Hoy</p>
                <p class="font-bold text-lg text-indigo-600 dark:text-indigo-400">{{ b.citas_completadas_hoy }} <span class="text-xs font-normal text-slate-400">completadas</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Citas de Hoy -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
          <h2 class="text-lg font-bold text-slate-800 dark:text-white flex justify-between items-center">
            Agenda del Día
            <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              {{ datos.citas_hoy.length }} citas
            </span>
          </h2>
        </div>
        
        <div class="flex-1 overflow-y-auto max-h-[600px] p-2">
          <div v-if="datos.citas_hoy.length === 0" class="flex flex-col items-center justify-center h-full py-10 text-slate-500">
            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <p>No hay citas programadas para hoy.</p>
          </div>
          
          <ul class="space-y-2" v-else>
            <li v-for="(cita, idx) in datos.citas_hoy" :key="idx" class="p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-600">
              <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ cita.hora }}</span>
                <span :class="{
                  'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400': cita.estado === 'Completada',
                  'text-amber-600 bg-amber-50 dark:bg-amber-900/30 dark:text-amber-400': cita.estado === 'Confirmada',
                  'text-red-600 bg-red-50 dark:bg-red-900/30 dark:text-red-400': cita.estado === 'Ausente' || cita.estado === 'Cancelada'
                }" class="text-[10px] uppercase font-bold px-2 py-1 rounded-md">
                  {{ cita.estado }}
                </span>
              </div>
              <p class="font-medium text-slate-800 dark:text-slate-200">{{ cita.cliente }}</p>
              <p class="text-xs text-slate-500 mb-2">con {{ cita.barbero }}</p>
              <p class="text-xs text-indigo-600 dark:text-indigo-400">{{ cita.servicios }}</p>
            </li>
          </ul>
        </div>
      </div>
      
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

const datos = ref(null)
const cargando = ref(true)

const cargarDashboard = async () => {
  cargando.value = true
  try {
    const res = await dashboardService.getDashboardAdmin()
    datos.value = res
  } catch (error) {
    console.error('Error cargando dashboard admin:', error)
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  cargarDashboard()
})
</script>