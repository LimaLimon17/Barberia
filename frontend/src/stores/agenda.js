import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { agendaService } from '../services/agendaService.js'

export const useAgendaStore = defineStore('agenda', () => {
  const modo = ref('hoy') // 'hoy' | 'busqueda'

  const citasHoy = ref([])
  const cargandoHoy = ref(false)
  const errorHoy = ref(null)

  const criterio = ref('')
  const resultadosBusqueda = ref([])
  const buscando = ref(false)
  const errorBusqueda = ref(null)
  const busquedaRealizada = ref(false)

  const idCambiandoEstado = ref(null) // evita doble click mientras se procesa
  const errorEstado = ref(null)

  const citasVisibles = computed(() =>
    modo.value === 'hoy' ? citasHoy.value : resultadosBusqueda.value
  )

  async function cargarCitasHoy() {
    cargandoHoy.value = true
    errorHoy.value = null
    try {
      const { data } = await agendaService.citasHoy()
      citasHoy.value = data.citas
    } catch {
      errorHoy.value = 'No se pudo cargar la agenda del día.'
    } finally {
      cargandoHoy.value = false
    }
  }

  async function buscar() {
    if (!criterio.value || criterio.value.trim().length < 2) return
    buscando.value = true
    errorBusqueda.value = null
    try {
      const { data } = await agendaService.buscarCitas(criterio.value.trim())
      resultadosBusqueda.value = data.citas
      busquedaRealizada.value = true
    } catch {
      errorBusqueda.value = 'No se pudo realizar la búsqueda.'
      resultadosBusqueda.value = []
    } finally {
      buscando.value = false
    }
  }

  function cambiarModo(nuevoModo) {
    modo.value = nuevoModo
    if (nuevoModo === 'hoy' && citasHoy.value.length === 0) {
      cargarCitasHoy()
    }
  }

  function limpiarBusqueda() {
    criterio.value = ''
    resultadosBusqueda.value = []
    busquedaRealizada.value = false
  }

  // Actualiza la cita tanto en la lista de hoy como en resultados de búsqueda,
  // sin necesidad de recargar todo desde el servidor.
  function actualizarCitaLocal(citaActualizada) {
    const reemplazar = (lista) => {
      const idx = lista.findIndex((c) => c.id_reserva === citaActualizada.id_reserva)
      if (idx >= 0) lista[idx] = citaActualizada
    }
    reemplazar(citasHoy.value)
    reemplazar(resultadosBusqueda.value)
  }

  async function cambiarEstado(idReserva, estado) {
    idCambiandoEstado.value = idReserva
    errorEstado.value = null
    try {
      const { data } = await agendaService.cambiarEstado(idReserva, estado)
      actualizarCitaLocal(data.cita)
      return true
    } catch (err) {
      errorEstado.value = err.response?.data?.error || 'No se pudo actualizar el estado de la cita.'
      return false
    } finally {
      idCambiandoEstado.value = null
    }
  }

  return {
    modo, citasHoy, cargandoHoy, errorHoy,
    criterio, resultadosBusqueda, buscando, errorBusqueda, busquedaRealizada,
    idCambiandoEstado, errorEstado,
    citasVisibles,
    cargarCitasHoy, buscar, cambiarModo, limpiarBusqueda, cambiarEstado,
  }
})