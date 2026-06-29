import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { reservaService } from '../services/reservaService.js'

export const useReservaStore = defineStore('reserva', () => {
  // ── Navegación del wizard ──────────────────────────────
  const paso = ref(1)

  // ── Paso 1: datos del cliente ──────────────────────────
  const cliente = ref({ CI: '', Nombre1: '', Apellido1: '', Telefono: '', Correo: '' })
  const clienteEncontrado = ref(false)
  const buscandoCliente = ref(false)

  // ── Paso 2: barbero ─────────────────────────────────────
  const barberos = ref([])
  const cargandoBarberos = ref(false)
  const idBarberoSeleccionado = ref(null)

  // ── Paso 3: categoría, servicios, fecha y horario ──────
  const categorias = ref([])
  const servicios = ref([])
  const cargandoServicios = ref(false)
  const idCategoriaFiltro = ref('')
  const serviciosSeleccionados = ref([]) // array de objetos Servicio completos

  const fechaCita = ref('') // YYYY-MM-DD
  const slots = ref([])
  const cargandoSlots = ref(false)
  const horaInicioSeleccionada = ref(null)
  const duracionTotalMinutos = ref(0)
  const costoTotalBackend = ref(0)
  const montoAnticipoBackend = ref(0)

  // ── Paso 4: reserva creada + pago QR ───────────────────
  const reservaActual = ref(null) // { IdReserva, EstadoReserva, ... }
  const qrPago = ref(null)
  const segundosRestantes = ref(0)
  const error = ref(null)

  // ── Computeds ───────────────────────────────────────────
  const costoTotal = computed(() =>
    serviciosSeleccionados.value.reduce((acc, s) => acc + Number(s.Precio), 0)
  )
  const duracionServiciosMinutos = computed(() =>
    serviciosSeleccionados.value.reduce((acc, s) => acc + Number(s.DuracionMinutos), 0)
  )
  // 10 min limpieza + 5 min tolerancia (igual que en backend)
  const duracionBloqueEstimada = computed(() => duracionServiciosMinutos.value + 10 + 5)
  const montoAnticipo = computed(() => Math.round(costoTotal.value * 0.5 * 100) / 100)
  const barberoSeleccionado = computed(() =>
    barberos.value.find((b) => b.id_barbero === idBarberoSeleccionado.value) || null
  )

  // ── Acciones Paso 1 ─────────────────────────────────────
  async function buscarClientePorCI(ci) {
    buscandoCliente.value = true
    error.value = null
    try {
      const { data } = await reservaService.buscarClientePorCI(ci)
      clienteEncontrado.value = data.encontrado
      if (data.encontrado) {
        cliente.value = {
          CI: data.cliente.CI,
          Nombre1: data.cliente.Nombre1,
          Apellido1: data.cliente.Apellido1,
          Telefono: data.cliente.Telefono,
          Correo: data.cliente.Correo,
        }
      } else {
        cliente.value = { CI: ci, Nombre1: '', Apellido1: '', Telefono: '', Correo: '' }
      }
      return data.encontrado
    } catch (err) {
      error.value = 'No se pudo verificar la CI en este momento.'
      return false
    } finally {
      buscandoCliente.value = false
    }
  }

  // ── Acciones Paso 2 ─────────────────────────────────────
  let pollingBarberos = null
  async function cargarBarberos() {
    cargandoBarberos.value = true
    try {
      const { data } = await reservaService.disponibilidadBarberos()
      barberos.value = data.barberos
    } catch (err) {
      error.value = 'No se pudo cargar la disponibilidad de barberos.'
    } finally {
      cargandoBarberos.value = false
    }
  }
  function iniciarPollingBarberos() {
    cargarBarberos()
    pollingBarberos = setInterval(cargarBarberos, 30000) // tiempo real cada 30s
  }
  function detenerPollingBarberos() {
    if (pollingBarberos) clearInterval(pollingBarberos)
    pollingBarberos = null
  }
  function seleccionarBarbero(idBarbero) {
    idBarberoSeleccionado.value = idBarbero
  }

  // ── Acciones Paso 3 ─────────────────────────────────────
  async function cargarCategorias() {
  try {
    const { data } = await reservaService.categorias()
    categorias.value = data.categorias
  } catch (err) {
    error.value = 'No se pudieron cargar las categorías.'
  }
}
  async function cargarServicios() {
    cargandoServicios.value = true
    try {
      const { data } = await reservaService.serviciosPorCategoria(idCategoriaFiltro.value)
      servicios.value = data.servicios
    } catch (err) {
      error.value = 'No se pudieron cargar los servicios.'
    } finally {
      cargandoServicios.value = false
    }
  }
  function toggleServicio(servicio) {
    const idx = serviciosSeleccionados.value.findIndex((s) => s.IdServicio === servicio.IdServicio)
    if (idx >= 0) {
      serviciosSeleccionados.value.splice(idx, 1)
    } else {
      serviciosSeleccionados.value.push(servicio)
    }
    // cambiar servicios invalida el horario elegido previamente
    horaInicioSeleccionada.value = null
    slots.value = []
  }
  async function cargarSlotsDisponibles() {
    if (!idBarberoSeleccionado.value || !fechaCita.value || serviciosSeleccionados.value.length === 0) {
      slots.value = []
      return
    }
    cargandoSlots.value = true
    error.value = null
    try {
      const { data } = await reservaService.slotsDisponibles({
        idBarbero: idBarberoSeleccionado.value,
        fecha: fechaCita.value,
        servicios: serviciosSeleccionados.value.map((s) => s.IdServicio),
      })
      slots.value = data.slots
      duracionTotalMinutos.value = data.duracion_total_minutos
      costoTotalBackend.value = data.costo_total
      montoAnticipoBackend.value = data.monto_anticipo
    } catch (err) {
      error.value = 'No se pudieron calcular los horarios disponibles.'
      slots.value = []
    } finally {
      cargandoSlots.value = false
    }
  }
  function seleccionarHorario(horaInicio) {
    horaInicioSeleccionada.value = horaInicio
  }

 // ── Acciones Paso 4 ─────────────────────────────────────
  let pollingEstado = null
  let intervaloVisual = null // <-- 1. Creamos una referencia para el timer visual

  async function confirmarReserva() {
    error.value = null
    try {
      const payload = {
        cliente: cliente.value,
        id_barbero: idBarberoSeleccionado.value,
        fecha_cita: fechaCita.value,
        hora_inicio: horaInicioSeleccionada.value,
        servicios: serviciosSeleccionados.value.map((s) => s.IdServicio),
      }
      const { data } = await reservaService.crearReserva(payload)
      reservaActual.value = data.reserva
      qrPago.value = data.qr
      segundosRestantes.value = data.qr.expira_en_segundos
      paso.value = 4
      
      iniciarPollingEstado() // <-- Esto activará ambos temporizadores
      return true
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        Object.values(err.response?.data?.errors || {}).flat().join(' ') ||
        'No se pudo registrar la reserva.'
      return false
    }
  }

  function iniciarPollingEstado() {
    // Aseguramos limpiar cualquier residuo previo antes de empezar
    detenerPollingEstado()

    // A) TIMER VISUAL: Resta 1 segundo en el cliente de forma fluida
    intervaloVisual = setInterval(() => {
      if (segundosRestantes.value > 0) {
        segundosRestantes.value--
      }
    }, 1000)

    // B) POLLING SERVIDOR: Sigue verificando el estado real cada 5 segundos
    pollingEstado = setInterval(async () => {
      if (!reservaActual.value) return
      try {
        const { data } = await reservaService.consultarEstado(reservaActual.value.IdReserva)
        
        // Sincronizamos con el servidor por si hay desfases de red
        segundosRestantes.value = data.segundos_restantes
        
        if (reservaActual.value) reservaActual.value.EstadoReserva = data.estado
        if (data.estado !== 'Pendiente') {
          detenerPollingEstado()
        }
      } catch (err) {
        // Silencioso
      }
    }, 5000)
  }

  function detenerPollingEstado() {
    if (pollingEstado) clearInterval(pollingEstado)
    if (intervaloVisual) clearInterval(intervaloVisual) // <-- 2. Limpiamos el timer visual
    pollingEstado = null
    intervaloVisual = null
  }

  async function pagarAnticipo() {
    error.value = null
    try {
      const { data } = await reservaService.confirmarPago(reservaActual.value.IdReserva, 'QR')
      reservaActual.value = data.reserva
      detenerPollingEstado()
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'No se pudo confirmar el pago.'
      // Refrescar estado para reflejar Expirada/Invalidada si corresponde.
      try {
        const { data } = await reservaService.consultarEstado(reservaActual.value.IdReserva)
        reservaActual.value.EstadoReserva = data.estado
      } catch {}
      return false
    }
  }

  function reiniciar() {
    paso.value = 1
    cliente.value = { CI: '', Nombre1: '', Apellido1: '', Telefono: '', Correo: '' }
    clienteEncontrado.value = false
    idBarberoSeleccionado.value = null
    idCategoriaFiltro.value = ''
    serviciosSeleccionados.value = []
    fechaCita.value = ''
    horaInicio.value = ''
  }

  return {
    paso,
    cliente,
    clienteEncontrado,
    buscandoCliente,
    barberos,
    cargandoBarberos,
    idBarberoSeleccionado,
    barberoSeleccionado,
    categorias,
    servicios,
    cargandoServicios,
    idCategoriaFiltro,
    serviciosSeleccionados,
    fechaCita,
    slots,
    cargandoSlots,
    horaInicioSeleccionada,
    duracionTotalMinutos,
    costoTotalBackend,
    montoAnticipoBackend,
    reservaActual,
    qrPago,
    segundosRestantes,
    error,
    costoTotal,
    duracionServiciosMinutos,
    duracionBloqueEstimada,
    montoAnticipo,
    buscarClientePorCI,
    cargarBarberos,
    iniciarPollingBarberos,
    detenerPollingBarberos,
    seleccionarBarbero,
    cargarCategorias,
    cargarServicios,
    toggleServicio,
    cargarSlotsDisponibles,
    seleccionarHorario,
    confirmarReserva,
    pagarAnticipo,
    detenerPollingEstado,
    reiniciar,
    irAPaso,
  }
})