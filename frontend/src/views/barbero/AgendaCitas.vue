<template>
  <div class="agenda-wrapper">

    <div class="agenda-header">
      <button @click="$emit('cerrar')" class="btn-volver">← Volver</button>
      <h2 class="agenda-titulo">Mi Agenda</h2>
    </div>

    <!-- Tabs -->
    <div class="tabs-wrap">
      <button :class="['tab-btn', store.modo === 'hoy' ? 'activo' : '']" @click="store.cambiarModo('hoy')">
        Hoy
      </button>
      <button :class="['tab-btn', store.modo === 'busqueda' ? 'activo' : '']" @click="store.cambiarModo('busqueda')">
        Buscar cita
      </button>
    </div>

    <!-- Buscador -->
    <div v-if="store.modo === 'busqueda'" class="buscador-wrap">
      <div class="buscador-fila">
        <input
          v-model="store.criterio"
          type="text"
          placeholder="Nombre, teléfono o CI del cliente"
          class="campo-input"
          @keyup.enter="store.buscar"
        />
        <button class="btn-buscar" :disabled="store.criterio.trim().length < 2 || store.buscando" @click="store.buscar">
          {{ store.buscando ? 'Buscando…' : 'Buscar' }}
        </button>
      </div>
      <p v-if="store.errorBusqueda" class="aviso-error">{{ store.errorBusqueda }}</p>
      <p v-if="store.busquedaRealizada && !store.buscando && store.resultadosBusqueda.length === 0" class="aviso-gris">
        No se encontraron citas futuras con ese criterio.
      </p>
    </div>

    <p v-if="store.errorEstado" class="aviso-error">{{ store.errorEstado }}</p>

    <!-- Lista -->
    <div v-if="store.modo === 'hoy' && store.cargandoHoy" class="loading-msg">Cargando agenda…</div>
    <div v-else-if="store.modo === 'hoy' && store.errorHoy" class="aviso-error">{{ store.errorHoy }}</div>
    <div v-else-if="store.modo === 'hoy' && store.citasHoy.length === 0" class="aviso-gris">
      No tienes citas registradas para hoy.
    </div>

    <div class="citas-lista">
      <div v-for="c in store.citasVisibles" :key="c.id_reserva" class="cita-card">
        <div class="cita-card-cabecera">
          <div class="cita-card-hora">
            <strong>{{ c.hora_inicio?.slice(0, 5) }}</strong>
            <span>– {{ c.hora_fin?.slice(0, 5) }}</span>
            <span v-if="store.modo === 'busqueda'" class="cita-card-fecha">{{ c.fecha }}</span>
          </div>
          <span :class="['estado-tag', `estado-${c.estado.toLowerCase()}`]">{{ c.estado }}</span>
        </div>

        <div class="cita-card-cliente">
          <span class="cliente-nombre">{{ c.cliente.nombre || 'Sin nombre' }}</span>
          <span class="cliente-meta">CI: {{ c.cliente.ci }} · {{ c.cliente.telefono || 'sin teléfono' }}</span>
        </div>

        <div class="cita-card-servicios">
          <span v-for="(s, i) in c.servicios" :key="i" class="servicio-chip">{{ s.nombre }}</span>
        </div>

        <div class="cita-card-footer">
          <span class="cita-card-monto">{{ c.costo_total.toFixed(0) }} Bs. total</span>

         <div v-if="c.estado === 'Confirmada'" class="cita-card-acciones">
  <button
    class="btn-accion ausente"
    :disabled="c.pago_completo || !puedeMarcarAusente(c) || store.idCambiandoEstado === c.id_reserva"
    :title="c.pago_completo
      ? 'Esta cita fue pagada al 100% — no aplica retención por ausencia'
      : !puedeMarcarAusente(c)
        ? `Disponible desde las ${horaLimiteAusente(c)}`
        : ''"
    @click="confirmarYCambiar(c, 'Ausente')"
  >
    Marcar Ausente
  </button>
  <button
    class="btn-accion completada"
    :disabled="store.idCambiandoEstado === c.id_reserva"
    @click="confirmarYCambiar(c, 'Completada')"
  >
    Marcar Completada
  </button>
</div>
                <div v-else-if="c.estado === 'Completada'" class="cita-card-acciones">
  <button class="btn-accion vender" @click="abrirVenta(c)">
    🛒 Vender productos
  </button>
    <button class="btn-accion cobrar" @click="abrirPagoFinal(c)">💳 Cobrar saldo final</button>
</div>

        </div>
      </div>
    </div>

  </div>
  <VenderProductos
  v-if="ventaAbierta"
  :id-reserva="citaParaVenta.id_reserva"
  :nombre-cliente="citaParaVenta.cliente.nombre"
  @cerrar="cerrarVenta"
/>
<PagoFinalModal v-if="pagoFinalAbierto" :id-reserva="idReservaPago" @cerrar="cerrarPagoFinal" @completado="pagoCompletado" />
</template>


<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useAgendaStore } from '../../stores/agenda.js'
import VenderProductos from './VenderProductos.vue'
import PagoFinalModal from '../../components/barbero/PagoFinalModal.vue'

const emit = defineEmits(['cerrar'])
const store = useAgendaStore()

// Reloj interno para reevaluar la elegibilidad de "Marcar Ausente" cada 30s
const tickAhora = ref(Date.now())
let intervalo = null
const pagoFinalAbierto = ref(false)
const idReservaPago = ref(null)

onMounted(() => {
  store.cargarCitasHoy()
  intervalo = setInterval(() => { tickAhora.value = Date.now() }, 30000)
})

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo)
})

const ventaAbierta = ref(false)
const citaParaVenta = ref(null)

function abrirVenta(cita) {
  citaParaVenta.value = cita
  ventaAbierta.value = true
}

function cerrarVenta() {
  ventaAbierta.value = false
  citaParaVenta.value = null
}
function fechaHoraInicio(cita) {
  // tickAhora.value se referencia para forzar reevaluación reactiva cada 30s
  void tickAhora.value
  return new Date(`${cita.fecha}T${cita.hora_inicio}`)
}

function puedeMarcarAusente(cita) {
  const inicio = fechaHoraInicio(cita)
  const umbral = new Date(inicio.getTime() + 5 * 60000)
  return Date.now() >= umbral.getTime()
}

function horaLimiteAusente(cita) {
  const inicio = fechaHoraInicio(cita)
  const umbral = new Date(inicio.getTime() + 5 * 60000)
  return umbral.toTimeString().slice(0, 5)
}

async function confirmarYCambiar(cita, estado) {
  const mensaje = estado === 'Ausente'
    ? `¿Confirmas que ${cita.cliente.nombre || 'el cliente'} no se presentó? Se retendrá el 50% del anticipo.`
    : `¿Confirmas que el servicio para ${cita.cliente.nombre || 'el cliente'} fue completado?`

  if (!window.confirm(mensaje)) return
  await store.cambiarEstado(cita.id_reserva, estado)
}
function abrirPagoFinal(cita) {
  idReservaPago.value = cita.id_reserva
  pagoFinalAbierto.value = true
}
function cerrarPagoFinal() {
  pagoFinalAbierto.value = false
  idReservaPago.value = null
}
function pagoCompletado() {
  cerrarPagoFinal()
  store.cargarCitasHoy() // refresca por si quieres reflejar algo en la lista
}
</script>

<style scoped>
.agenda-wrapper { max-width: 760px; margin: 0 auto; padding: 0 0 2rem; color: var(--color-text-primary); }
.agenda-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.btn-volver { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; white-space: nowrap; transition: all 0.2s; }
.btn-volver:hover { border-color: var(--color-gold); color: var(--color-gold); }
.agenda-titulo { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; margin: 0; }

.tabs-wrap { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); }
.tab-btn { background: transparent; border: none; border-bottom: 2px solid transparent; color: var(--color-text-secondary); padding: 0.65rem 0.25rem; margin-right: 1.25rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.tab-btn:hover { color: var(--color-text-primary); }
.tab-btn.activo { color: var(--color-gold); border-bottom-color: var(--color-gold); }

.buscador-wrap { margin-bottom: 1.5rem; }
.buscador-fila { display: flex; gap: 0.6rem; }
.campo-input { flex: 1; background: var(--color-bg-input, rgba(255,255,255,0.05)); border: 1px solid var(--color-border); border-radius: 7px; padding: 0.6rem 0.85rem; font-size: 0.875rem; color: var(--color-text-primary); }
.campo-input:focus { outline: none; border-color: var(--color-gold); }
.btn-buscar { padding: 0.6rem 1.2rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
.btn-buscar:disabled { opacity: 0.5; cursor: not-allowed; }

.aviso-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.82rem; margin-bottom: 1.25rem; }
.aviso-gris { font-size: 0.82rem; color: var(--color-text-secondary); padding: 1.5rem 0; text-align: center; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 2rem 0; }

.citas-lista { display: flex; flex-direction: column; gap: 0.85rem; }
.cita-card { border: 1px solid var(--color-border); border-radius: 10px; padding: 1rem 1.25rem; background: var(--color-bg-card); }
.cita-card-cabecera { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; }
.cita-card-hora { display: flex; align-items: baseline; gap: 0.4rem; font-size: 0.9rem; }
.cita-card-hora strong { font-family: var(--font-heading); font-size: 1.05rem; }
.cita-card-fecha { color: var(--color-text-secondary); font-size: 0.75rem; margin-left: 0.5rem; }

.estado-tag { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; padding: 0.25rem 0.6rem; border-radius: 4px; white-space: nowrap; }
.estado-confirmada { background: rgba(22,163,74,0.15); color: #4ade80; }
.estado-completada { background: rgba(201,168,76,0.15); color: var(--color-gold); }
.estado-ausente { background: rgba(239,68,68,0.15); color: #f87171; }

.cita-card-cliente { display: flex; flex-direction: column; gap: 0.1rem; margin-bottom: 0.6rem; }
.cliente-nombre { font-weight: 600; font-size: 0.9rem; }
.cliente-meta { font-size: 0.75rem; color: var(--color-text-secondary); }

.cita-card-servicios { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
.servicio-chip { font-size: 0.7rem; padding: 0.2rem 0.6rem; background: rgba(255,255,255,0.05); border: 1px solid var(--color-border); border-radius: 20px; color: var(--color-text-secondary); }

.cita-card-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.6rem; padding-top: 0.6rem; border-top: 1px solid var(--color-border); }
.cita-card-monto { font-size: 0.8rem; color: var(--color-text-secondary); font-weight: 600; }
.cita-card-acciones { display: flex; gap: 0.5rem; }
.btn-accion { padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; transition: opacity 0.2s; }
.btn-accion:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-accion.ausente { background: rgba(239,68,68,0.12); color: #f87171; border-color: rgba(239,68,68,0.3); }
.btn-accion.ausente:hover:not(:disabled) { background: rgba(239,68,68,0.2); }
.btn-accion.completada { background: rgba(22,163,74,0.15); color: #4ade80; border-color: rgba(22,163,74,0.3); }
.btn-accion.completada:hover:not(:disabled) { background: rgba(22,163,74,0.25); }
.btn-accion.vender { background: rgba(201,168,76,0.12); color: var(--color-gold); border-color: rgba(201,168,76,0.3); }
.btn-accion.vender:hover:not(:disabled) { background: rgba(201,168,76,0.2); }
.btn-accion.cobrar { background: rgba(34,197,94,0.12); color: #4ade80; border-color: rgba(34,197,94,0.3); }
.btn-accion.cobrar:hover:not(:disabled) { background: rgba(34,197,94,0.2); }
</style>
