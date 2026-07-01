import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { citaPresencialService } from '../services/citaPresencialService.js'

export const useCitaPresencialStore = defineStore('citaPresencial', () => {

  // ── Tabs / Navegación ────────────────────────────────────────────
  const tabActiva = ref('nueva') // 'nueva' | 'citas'
  const paso = ref(1) // 1:Cliente  2:Servicios+Horario  3:Pago  4:Éxito

  // ── Info del barbero ─────────────────────────────────────────────
  const idBarbero = ref(null)
  const nombreBarbero = ref('')

  // ── Paso 1: cliente ──────────────────────────────────────────────
  const cliente = ref({
    ci: '', nombre1: '', nombre2: '',
    apellido1: '', apellido2: '',
    telefono: '', correo: '',
  })
  const clienteEncontrado = ref(false)
  const buscandoCliente   = ref(false)

  // ── Paso 2: categorías, servicios, fecha y horario ──────────────
  const categorias             = ref([])
  const servicios              = ref([])
  const idCategoriaFiltro      = ref('')
  const serviciosSeleccionados = ref([])
  const cargandoServicios      = ref(false)

  const fechaCita              = ref('')
  const slots                  = ref([])
  const horaInicioSeleccionada = ref(null)
  const duracionTotal          = ref(0)
  const cargandoSlots          = ref(false)
  
const hoyStr = new Date().toISOString().split('T')[0]

const esCitaHoy = computed(() => fechaCita.value === hoyStr)

const montoACobrar = computed(() =>
  esCitaHoy.value ? costoTotal.value : Math.round(costoTotal.value * 0.5 * 100) / 100
)
  // ── Paso 3: pago ─────────────────────────────────────────────────
  const metodoPago      = ref('Efectivo') // 'Efectivo' | 'QR'
  const reservaPendiente = ref(null)       // { id_reserva, qr } mientras se espera confirmar el QR

  // ── Paso 4: resultado ────────────────────────────────────────────
  const reservaConfirmada = ref(null)
  const error             = ref(null)
  const cargando          = ref(false)

  // ── Listado de citas ─────────────────────────────────────────────
  const citasLista   = ref([])
  const cargandoCitas = ref(false)
  const errorCitas    = ref(null)
  const hoy = new Date().toISOString().split('T')[0]
  const filtroDesde = ref(hoy)
  const filtroHasta = ref(hoy)

  // ── Computeds ─────────────────────────────────────────────────────
  const costoTotal = computed(() =>
    serviciosSeleccionados.value.reduce((acc, s) => acc + Number(s.Precio), 0)
  )

  const serviciosFiltrados = computed(() => {
    if (!idCategoriaFiltro.value) return servicios.value
    return servicios.value.filter(s => s.IdCategoria === idCategoriaFiltro.value)
  })

  const horaFinEstimada = computed(() => {
    if (!horaInicioSeleccionada.value || !duracionTotal.value) return null
    const [h, m] = horaInicioSeleccionada.value.split(':').map(Number)
    const total  = h * 60 + m + duracionTotal.value
    const hh     = String(Math.floor(total / 60)).padStart(2, '0')
    const mm     = String(total % 60).padStart(2, '0')
    return `${hh}:${mm}`
  })

  // ── Inicializar ───────────────────────────────────────────────────
  async function inicializar() {
    cargando.value = true
    error.value    = null
    try {
      const { data } = await citaPresencialService.inicializar()
      idBarbero.value     = data.id_barbero
      nombreBarbero.value = data.nombre
      categorias.value    = data.categorias
      await cargarServicios()
    } catch (err) {
      error.value = 'No se pudo inicializar el formulario.'
    } finally {
      cargando.value = false
    }
  }

  // ── Paso 1: buscar cliente ────────────────────────────────────────
  async function buscarCliente() {
    if (!cliente.value.ci) return
    buscandoCliente.value = true
    error.value = null
    try {
      const { data } = await citaPresencialService.buscarClientePorCI(cliente.value.ci)
      clienteEncontrado.value = data.encontrado
      if (data.encontrado && data.cliente) {
        const c = data.cliente
        cliente.value = {
          ci:        c.CI,
          nombre1:   c.Nombre1   || '',
          nombre2:   c.Nombre2   || '',
          apellido1: c.Apellido1 || '',
          apellido2: c.Apellido2 || '',
          telefono:  c.Telefono  || '',
          correo:    c.Correo    || '',
        }
      }
    } catch {
      error.value = 'Error al buscar el cliente.'
    } finally {
      buscandoCliente.value = false
    }
  }

  // ── Paso 2: servicios ─────────────────────────────────────────────
  async function cargarServicios() {
    cargandoServicios.value = true
    try {
      const { data } = await citaPresencialService.servicios(idCategoriaFiltro.value)
      servicios.value = data.servicios
    } catch {
      error.value = 'No se pudieron cargar los servicios.'
    } finally {
      cargandoServicios.value = false
    }
  }

  function toggleServicio(servicio) {
    const idx = serviciosSeleccionados.value.findIndex(s => s.IdServicio === servicio.IdServicio)
    if (idx >= 0) {
      serviciosSeleccionados.value.splice(idx, 1)
    } else {
      serviciosSeleccionados.value.push(servicio)
    }
    horaInicioSeleccionada.value = null
    slots.value = []
  }

  function estaSeleccionado(idServicio) {
    return serviciosSeleccionados.value.some(s => s.IdServicio === idServicio)
  }

  // ── Paso 2: slots ─────────────────────────────────────────────────
  async function cargarSlots() {
    if (!fechaCita.value || serviciosSeleccionados.value.length === 0) return
    cargandoSlots.value = true
    error.value = null
    try {
      const { data } = await citaPresencialService.slots({
        fecha:     fechaCita.value,
        servicios: serviciosSeleccionados.value.map(s => s.IdServicio),
      })
      slots.value          = data.slots
      duracionTotal.value  = data.duracion_total_minutos
    } catch {
      error.value = 'No se pudieron cargar los horarios disponibles.'
      slots.value = []
    } finally {
      cargandoSlots.value = false
    }
  }

  // ── Paso 3: crear cita (Efectivo confirma directo / QR queda pendiente) ──
  async function crearCita() {
    cargando.value = true
    error.value    = null
    try {
      const { data } = await citaPresencialService.crear({
        ci:          cliente.value.ci,
        nombre1:     cliente.value.nombre1,
        nombre2:     cliente.value.nombre2,
        apellido1:   cliente.value.apellido1,
        apellido2:   cliente.value.apellido2,
        telefono:    cliente.value.telefono,
        correo:      cliente.value.correo,
        servicios:   serviciosSeleccionados.value.map(s => s.IdServicio),
        fecha:       fechaCita.value,
        hora_inicio: horaInicioSeleccionada.value,
        metodo_pago: metodoPago.value,
      })

      if (data.pendiente) {
        // QR generado, esperando confirmación de pago
        reservaPendiente.value = { id_reserva: data.id_reserva, qr: data.qr }
      } else {
        reservaConfirmada.value = data
        paso.value = 4
      }
      return true
    } catch (err) {
      error.value = err.response?.data?.error || 'No se pudo crear la cita.'
      return false
    } finally {
      cargando.value = false
    }
  }

  // ── Paso 3: confirmar que el QR ya fue pagado ─────────────────────
  async function confirmarPagoQR() {
    if (!reservaPendiente.value) return false
    cargando.value = true
    error.value    = null
    try {
      const { data } = await citaPresencialService.confirmarPago(reservaPendiente.value.id_reserva)
      reservaConfirmada.value = data
      reservaPendiente.value  = null
      paso.value = 4
      return true
    } catch (err) {
      error.value = err.response?.data?.error || 'No se pudo confirmar el pago.'
      return false
    } finally {
      cargando.value = false
    }
  }

  // ── Listado de citas ───────────────────────────────────────────────
  async function cargarCitas() {
    cargandoCitas.value = true
    errorCitas.value    = null
    try {
      const { data } = await citaPresencialService.misCitas(filtroDesde.value, filtroHasta.value)
      citasLista.value = data.citas
    } catch {
      errorCitas.value = 'No se pudieron cargar las citas.'
    } finally {
      cargandoCitas.value = false
    }
  }

  function cambiarTab(tab) {
    tabActiva.value = tab
    if (tab === 'citas' && citasLista.value.length === 0) {
      cargarCitas()
    }
  }

  // ── Reset ────────────────────────────────────────────────────────
  function reiniciar() {
    paso.value = 1
    cliente.value = { ci: '', nombre1: '', nombre2: '', apellido1: '', apellido2: '', telefono: '', correo: '' }
    clienteEncontrado.value = false
    serviciosSeleccionados.value = []
    idCategoriaFiltro.value = ''
    fechaCita.value = ''
    slots.value = []
    horaInicioSeleccionada.value = null
    metodoPago.value = 'Efectivo'
    reservaPendiente.value = null
    reservaConfirmada.value = null
    error.value = null
  }

  return {
    tabActiva, paso, idBarbero, nombreBarbero,
    cliente, clienteEncontrado, buscandoCliente,
    categorias, servicios, serviciosFiltrados, idCategoriaFiltro,
    serviciosSeleccionados, cargandoServicios,
    fechaCita, slots, horaInicioSeleccionada, duracionTotal, cargandoSlots,
    metodoPago, reservaPendiente,
    costoTotal, horaFinEstimada,
    reservaConfirmada, error, cargando,
    citasLista, cargandoCitas, errorCitas, filtroDesde, filtroHasta,
    inicializar, buscarCliente,
    cargarServicios, toggleServicio, estaSeleccionado,
    cargarSlots,
    crearCita, confirmarPagoQR,
    cargarCitas, cambiarTab,
    reiniciar,
    irAPaso: (n) => { paso.value = n },
  }
})