import { defineStore } from 'pinia'
import { ref } from 'vue'
import { comisionService } from '../services/comisionService.js'

export const useComisionStore = defineStore('comision', () => {
  const modo = ref('semana') // 'semana' | 'personalizado'

  const semana = ref(null)
  const anio = ref(null)
  const fechaInicio = ref(null)
  const fechaFin = ref(null)
  const consolidado = ref(false)
  const clienteFiltro = ref('')

  const bloques = ref([])
  const totales = ref({ servicios: 0, productos: 0, ausentes: 0, neto: 0 })

  const cargando = ref(false)
  const error = ref(null)

  // Campos editables del formulario de filtro personalizado
  const filtroDesde = ref(null)
  const filtroHasta = ref(null)
  const filtroCliente = ref('')

  function aplicarRespuesta(data) {
    fechaInicio.value = data.fecha_inicio
    fechaFin.value = data.fecha_fin
    bloques.value = data.bloques
    totales.value = {
      servicios: data.total_servicios,
      productos: data.total_productos,
      ausentes: data.total_ausentes,
      neto: data.total_neto,
    }
  }

  async function cargarSemana(semanaParam = null, anioParam = null) {
    modo.value = 'semana'
    cargando.value = true
    error.value = null
    try {
      const { data } = await comisionService.semana(semanaParam, anioParam)
      semana.value = data.semana
      anio.value = data.anio
      consolidado.value = data.consolidado
      aplicarRespuesta(data)
    } catch {
      error.value = 'No se pudo cargar el reporte de comisiones.'
    } finally {
      cargando.value = false
    }
  }

  async function cargarPersonalizado() {
    if (!filtroDesde.value || !filtroHasta.value) return
    modo.value = 'personalizado'
    cargando.value = true
    error.value = null
    try {
      const { data } = await comisionService.filtrar(filtroDesde.value, filtroHasta.value, filtroCliente.value)
      clienteFiltro.value = data.cliente_filtro
      aplicarRespuesta(data)
    } catch {
      error.value = 'No se pudo cargar el reporte filtrado.'
    } finally {
      cargando.value = false
    }
  }
// ── Helpers de fecha: todo en UTC explícito, sin mezclar con hora local ──

function parseISO(fechaStr) {
  const [y, m, d] = fechaStr.split('-').map(Number)
  return new Date(Date.UTC(y, m - 1, d))
}

function sumarDiasUTC(fechaUTC, dias) {
  const copia = new Date(fechaUTC.getTime())
  copia.setUTCDate(copia.getUTCDate() + dias)
  return copia
}

function isoSemanaDe(fechaUTC) {
  const d = new Date(fechaUTC.getTime())
  const dayNum = (d.getUTCDay() + 6) % 7
  d.setUTCDate(d.getUTCDate() - dayNum + 3)
  const firstThursday = new Date(Date.UTC(d.getUTCFullYear(), 0, 4))
  const semana = 1 + Math.round(((d - firstThursday) / 86400000 - 3 + ((firstThursday.getUTCDay() + 6) % 7)) / 7)
  return { semana, anio: d.getUTCFullYear() }
}

function semanaAnterior() {
  const fecha = sumarDiasUTC(parseISO(fechaInicio.value), -7)
  const { semana: s, anio: a } = isoSemanaDe(fecha)
  cargarSemana(s, a)
}

function semanaSiguiente() {
  const fecha = sumarDiasUTC(parseISO(fechaInicio.value), 7)
  const { semana: s, anio: a } = isoSemanaDe(fecha)
  cargarSemana(s, a)
}
  return {
    modo, semana, anio, fechaInicio, fechaFin, consolidado, clienteFiltro,
    bloques, totales, cargando, error,
    filtroDesde, filtroHasta, filtroCliente,
    cargarSemana, cargarPersonalizado, semanaAnterior, semanaSiguiente,
  }
})