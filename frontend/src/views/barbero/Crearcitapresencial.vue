<template>
  <div class="cita-wrapper">

    <div class="cita-header">
      <button @click="$emit('cerrar')" class="btn-volver">← Volver</button>
      <div>
        <h2 class="cita-titulo">Cita Presencial</h2>
        <p class="cita-sub">Barbero: <strong>{{ store.nombreBarbero }}</strong></p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-wrap">
      <button :class="['tab-btn', store.tabActiva === 'nueva' ? 'activo' : '']" @click="store.cambiarTab('nueva')">
        Nueva Cita
      </button>
      <button :class="['tab-btn', store.tabActiva === 'citas' ? 'activo' : '']" @click="store.cambiarTab('citas')">
        Mis Citas
      </button>
    </div>

    <!-- ════════════════ TAB: NUEVA CITA ════════════════ -->
    <template v-if="store.tabActiva === 'nueva'">

      <div class="stepper">
        <div
          v-for="s in pasos"
          :key="s.n"
          :class="['step', store.paso === s.n ? 'activo' : store.paso > s.n ? 'completo' : '']"
        >
          <div class="step-circulo">{{ store.paso > s.n ? '✓' : s.n }}</div>
          <span class="step-label">{{ s.label }}</span>
        </div>
      </div>

      <div v-if="store.error" class="alerta-error">⚠ {{ store.error }}</div>

      <!-- PASO 1 — Cliente -->
      <div v-if="store.paso === 1" class="paso-contenido">
        <h3 class="paso-titulo">Datos del Cliente</h3>

        <div class="campo-grupo">
          <label class="campo-label">Carnet de Identidad *</label>
          <div class="ci-row">
            <input
              v-model="store.cliente.ci"
              @keyup.enter="buscarYmarcar"
              type="text"
              :class="['campo-input', tocado.ci && errorCI ? 'input-error' : '']"
              placeholder="Ej: 12345678"
              maxlength="10"
              @blur="tocado.ci = true"
              @input="resetearBusqueda"
            />
            <button
              @click="buscarYmarcar"
              :disabled="!store.cliente.ci || errorCI || store.buscandoCliente"
              class="btn-buscar"
            >
              {{ store.buscandoCliente ? 'Buscando…' : 'Buscar' }}
            </button>
          </div>
          <p v-if="tocado.ci && !store.cliente.ci" class="campo-error">Ingresa el CI para continuar.</p>
          <p v-else-if="tocado.ci && errorCI" class="campo-error">La cédula debe tener entre 4 y 10 dígitos numéricos.</p>
          <p v-if="store.clienteEncontrado" class="aviso-verde" style="margin-top:0.25rem">
            ✓ Cliente encontrado — datos autocompletados.
          </p>
          <p v-else-if="store.cliente.ci && !store.buscandoCliente && ciYaBuscado && !errorCI" class="aviso-gris" style="margin-top:0.25rem">
            Cliente nuevo — completa los datos manualmente.
          </p>
        </div>

        <div class="form-grid">
          <div class="campo-grupo">
            <label class="campo-label">Primer Nombre *</label>
            <input
              v-model="store.cliente.nombre1"
              type="text"
              :class="['campo-input', tocado.nombre1 && errorNombre ? 'input-error' : '']"
              placeholder="Nombre"
              @blur="tocado.nombre1 = true"
            />
            <p v-if="tocado.nombre1 && !store.cliente.nombre1" class="campo-error">Ingresa el nombre.</p>
            <p v-else-if="tocado.nombre1 && errorNombre" class="campo-error">Solo letras.</p>
          </div>

          <div class="campo-grupo">
            <label class="campo-label">Primer Apellido *</label>
            <input
              v-model="store.cliente.apellido1"
              type="text"
              :class="['campo-input', tocado.apellido1 && errorApellido ? 'input-error' : '']"
              placeholder="Apellido"
              @blur="tocado.apellido1 = true"
            />
            <p v-if="tocado.apellido1 && !store.cliente.apellido1" class="campo-error">Ingresa el apellido.</p>
            <p v-else-if="tocado.apellido1 && errorApellido" class="campo-error">Solo letras.</p>
          </div>

          <div class="campo-grupo">
            <label class="campo-label">Teléfono/Celular *</label>
            <input
              v-model="store.cliente.telefono"
              type="text"
              inputmode="numeric"
              :class="['campo-input', tocado.telefono && errorTelefono ? 'input-error' : '']"
              placeholder="Ej: 77712345"
              maxlength="10"
              @blur="tocado.telefono = true"
            />
            <p v-if="tocado.telefono && !store.cliente.telefono" class="campo-error">Ingresa el número de contacto.</p>
            <p v-else-if="tocado.telefono && errorTelefono" class="campo-error">8 a 10 dígitos numéricos.</p>
          </div>

          <div class="campo-grupo">
            <label class="campo-label">Correo *</label>
            <input
              v-model="store.cliente.correo"
              type="email"
              :class="['campo-input', tocado.correo && errorCorreo ? 'input-error' : '']"
              placeholder="correo@ejemplo.com"
              @blur="tocado.correo = true"
            />
            <p v-if="tocado.correo && !store.cliente.correo" class="campo-error">Ingresa el correo.</p>
            <p v-else-if="tocado.correo && errorCorreo" class="campo-error">Formato inválido. Ej: ejemplo@gmail.com</p>
          </div>
        </div>

        <transition name="fade-bloque">
          <div v-if="intentoContinuar && !paso1Valido" class="banner-error-local">
            <strong>Faltan datos obligatorios o inválidos:</strong>
            Revisa las alertas en rojo para continuar.
          </div>
        </transition>

        <div class="paso-footer">
          <button @click="avanzarPaso1" class="btn-primario">Continuar →</button>
        </div>
      </div>

      <!-- PASO 2 — Servicios + Horario (UNIDOS) -->
      <div v-else-if="store.paso === 2" class="paso-contenido">
        <h3 class="paso-titulo">Servicios y Horario</h3>

        <p class="subseccion-label">Selecciona los servicios</p>
        <div class="filtros-wrap">
          <button @click="cambiarCategoria('')" :class="['filtro-btn', !store.idCategoriaFiltro ? 'activo' : '']">
            Todos
          </button>
          <button
            v-for="cat in store.categorias"
            :key="cat.IdCategoria"
            @click="cambiarCategoria(cat.IdCategoria)"
            :class="['filtro-btn', store.idCategoriaFiltro === cat.IdCategoria ? 'activo' : '']"
          >
            {{ cat.Nombre }}
          </button>
        </div>

        <div v-if="store.cargandoServicios" class="loading-msg">Cargando servicios…</div>
        <div v-else class="servicios-lista">
          <div
            v-for="s in store.serviciosFiltrados"
            :key="s.IdServicio"
            @click="onToggleServicio(s)"
            :class="['servicio-item', store.estaSeleccionado(s.IdServicio) ? 'seleccionado' : '']"
          >
            <div class="servicio-check">
              <span v-if="store.estaSeleccionado(s.IdServicio)">✓</span>
            </div>
            <div class="servicio-info">
              <span class="servicio-nombre">{{ s.Nombre }}</span>
              <span class="servicio-meta">{{ s.DuracionMinutos }} min</span>
            </div>
            <span class="servicio-precio">{{ Number(s.Precio).toFixed(0) }} Bs.</span>
          </div>
        </div>

        <div v-if="store.serviciosSeleccionados.length > 0" class="resumen-servicios">
          <div class="resumen-fila">
            <span>{{ store.serviciosSeleccionados.length }} servicio(s)</span>
            <strong>{{ store.costoTotal.toFixed(0) }} Bs.</strong>
          </div>
        </div>

        <template v-if="store.serviciosSeleccionados.length > 0">
          <p class="subseccion-label" style="margin-top:1.75rem">Elige la fecha y el horario</p>

          <div class="campo-grupo" style="margin-bottom:1.25rem">
            <label class="campo-label">Fecha de la cita *</label>
            <input
              v-model="store.fechaCita"
              @change="cargarSlotsYautoseleccionar"
              type="date"
              :min="hoy"
              class="campo-input campo-fecha"
            />
          </div>

          <div v-if="store.cargandoSlots" class="loading-msg">Calculando horarios…</div>

          <div v-else-if="store.fechaCita && slotsDisponibles.length === 0" class="aviso-gris">
            No hay horarios disponibles para esta fecha. Intenta con otra fecha.
          </div>

          <div v-else-if="slotsDisponibles.length > 0">
            <div v-if="store.horaInicioSeleccionada" class="proximo-slot-banner">
              <span class="proximo-label">Horario elegido</span>
              <span class="proximo-hora">{{ store.horaInicioSeleccionada }} → {{ store.horaFinEstimada }}</span>
              <span class="proximo-duracion">({{ store.duracionTotal }} min)</span>
            </div>

            <p class="campo-label" style="margin-bottom:0.6rem">Todos los turnos disponibles</p>
            <div class="slots-grid">
              <button
                v-for="slot in slotsDisponibles"
                :key="slot.hora_inicio"
                @click="store.horaInicioSeleccionada = slot.hora_inicio"
                :class="['slot-btn', store.horaInicioSeleccionada === slot.hora_inicio ? 'activo' : '']"
              >
                {{ slot.hora_inicio }}
              </button>
            </div>
          </div>
        </template>

        <div class="paso-footer">
          <button @click="store.irAPaso(1)" class="btn-secundario">← Atrás</button>
          <button
            @click="store.irAPaso(3)"
            :disabled="store.serviciosSeleccionados.length === 0 || !store.horaInicioSeleccionada"
            class="btn-primario"
          >
            Continuar →
          </button>
        </div>
      </div>

      <!-- PASO 3 — Pago -->
      <div v-else-if="store.paso === 3" class="paso-contenido">
        <h3 class="paso-titulo">Pago</h3>

        <div class="resumen-cita">
          <div class="resumen-seccion">
            <p class="resumen-titulo">Cliente</p>
            <p class="resumen-valor">{{ store.cliente.nombre1 }} {{ store.cliente.apellido1 }}</p>
            <p class="resumen-meta">CI: {{ store.cliente.ci }}</p>
          </div>

          <div class="resumen-seccion">
            <p class="resumen-titulo">Servicios</p>
            <div v-for="s in store.serviciosSeleccionados" :key="s.IdServicio" class="resumen-servicio-fila">
              <span>{{ s.Nombre }}</span>
              <span>{{ Number(s.Precio).toFixed(0) }} Bs.</span>
            </div>
          </div>

          <div class="resumen-seccion">
            <p class="resumen-titulo">Horario</p>
            <p class="resumen-valor">
              {{ store.fechaCita }} · {{ store.horaInicioSeleccionada }} – {{ store.horaFinEstimada }}
            </p>
          </div>

          <div class="resumen-total">
            <span>TOTAL A COBRAR</span>
            <strong>{{ store.costoTotal.toFixed(2) }} Bs.</strong>
          </div>
        </div>

        <!-- Selector de método (solo si aún no se generó el QR) -->
        <div v-if="!store.reservaPendiente" class="campo-grupo" style="margin-top:1.25rem">
          <label class="campo-label">Método de pago *</label>
          <div class="metodos-pago">
            <button
              v-for="mp in metodosPago"
              :key="mp.value"
              @click="store.metodoPago = mp.value"
              :class="['metodo-btn', store.metodoPago === mp.value ? 'activo' : '']"
            >
              <span class="metodo-icon">{{ mp.icon }}</span>
              <span>{{ mp.label }}</span>
            </button>
          </div>
        </div>

        <!-- Vista QR pendiente de confirmación -->
        <div v-else class="qr-bloque">
          <p class="qr-titulo">Cobra escaneando este código</p>
          <div class="qr-caja">
            <!-- Placeholder visual: aquí se integraría una librería real de QR -->
            <div class="qr-placeholder">
              <span class="qr-placeholder-icon">▦</span>
            </div>
            <p class="qr-referencia">{{ store.reservaPendiente.qr.referencia }}</p>
            <p class="qr-monto">{{ Number(store.reservaPendiente.qr.monto).toFixed(2) }} {{ store.reservaPendiente.qr.moneda }}</p>
          </div>
          <p class="qr-aviso">El cliente debe escanear y pagar antes de confirmar.</p>
        </div>

        <div class="paso-footer">
          <button @click="volverDesdePago" class="btn-secundario" :disabled="store.cargando">
            ← Atrás
          </button>

          <!-- Efectivo: un solo botón que confirma directo -->
          <button
            v-if="!store.reservaPendiente && store.metodoPago === 'Efectivo'"
            @click="store.crearCita"
            :disabled="store.cargando"
            class="btn-confirmar"
          >
            <span v-if="store.cargando">Registrando…</span>
            <span v-else>✓ Confirmar Cita · {{ store.costoTotal.toFixed(2) }} Bs.</span>
          </button>

          <!-- QR: primero generar el QR -->
          <button
            v-else-if="!store.reservaPendiente && store.metodoPago === 'QR'"
            @click="store.crearCita"
            :disabled="store.cargando"
            class="btn-confirmar"
          >
            <span v-if="store.cargando">Generando QR…</span>
            <span v-else>Generar QR de cobro</span>
          </button>

          <!-- QR generado: confirmar que ya pagó -->
          <button
            v-else-if="store.reservaPendiente"
            @click="store.confirmarPagoQR"
            :disabled="store.cargando"
            class="btn-confirmar"
          >
            <span v-if="store.cargando">Confirmando…</span>
            <span v-else>✓ Ya pagó — Confirmar</span>
          </button>
        </div>
      </div>

      <!-- PASO 4 — Éxito -->
      <div v-else-if="store.paso === 4" class="paso-contenido paso-exito">
        <div class="exito-icono">✓</div>
        <h3 class="exito-titulo">¡Cita Registrada!</h3>

        <div class="exito-detalle">
          <div class="detalle-fila">
            <span class="detalle-label">Reserva #</span>
            <span class="detalle-valor">{{ store.reservaConfirmada?.id_reserva }}</span>
          </div>
          <div class="detalle-fila">
            <span class="detalle-label">Cliente</span>
            <span class="detalle-valor">
              {{ store.reservaConfirmada?.cliente?.Nombre1 }}
              {{ store.reservaConfirmada?.cliente?.Apellido1 }}
            </span>
          </div>
          <div class="detalle-fila">
            <span class="detalle-label">Horario</span>
            <span class="detalle-valor">
              {{ store.fechaCita }} · {{ store.horaInicioSeleccionada }} – {{ store.horaFinEstimada }}
            </span>
          </div>
          <div class="detalle-fila">
            <span class="detalle-label">Total cobrado</span>
            <span class="detalle-valor cobrado">
              {{ Number(store.reservaConfirmada?.costo_total).toFixed(2) }} Bs.
              <span class="metodo-tag">{{ store.reservaConfirmada?.metodo_pago }}</span>
            </span>
          </div>
        </div>

        <div class="exito-acciones">
          <button @click="reiniciarFormularioCompleto" class="btn-primario">+ Nueva Cita</button>
          <button @click="$emit('cerrar')" class="btn-secundario">Volver al Dashboard</button>
        </div>
      </div>

    </template>

    <!-- ════════════════ TAB: MIS CITAS ════════════════ -->
    <template v-else>
      <div class="paso-contenido">
        <h3 class="paso-titulo">Mis Citas</h3>

        <div class="filtros-fecha">
          <div class="campo-grupo">
            <label class="campo-label">Desde</label>
            <input v-model="store.filtroDesde" type="date" class="campo-input" />
          </div>
          <div class="campo-grupo">
            <label class="campo-label">Hasta</label>
            <input v-model="store.filtroHasta" type="date" class="campo-input" />
          </div>
          <button @click="store.cargarCitas" class="btn-buscar" :disabled="store.cargandoCitas">
            {{ store.cargandoCitas ? 'Buscando…' : 'Buscar' }}
          </button>
        </div>

        <div v-if="store.errorCitas" class="alerta-error">⚠ {{ store.errorCitas }}</div>
        <div v-if="store.cargandoCitas" class="loading-msg">Cargando citas…</div>

        <div v-else-if="store.citasLista.length === 0" class="aviso-gris">
          No hay citas registradas en ese rango de fechas.
        </div>

        <div v-else class="citas-lista">
          <div v-for="c in store.citasLista" :key="c.id_reserva" class="cita-fila">
            <div class="cita-fila-fecha">
              <strong>{{ c.fecha }}</strong>
              <span>{{ c.hora_inicio }} – {{ c.hora_fin }}</span>
            </div>
            <div class="cita-fila-info">
              <span class="cita-fila-cliente">{{ c.cliente }}</span>
              <span class="cita-fila-servicios">{{ c.servicios.join(', ') }}</span>
            </div>
            <div class="cita-fila-estado">
              <span :class="['estado-tag', `estado-${c.estado.toLowerCase()}`]">{{ c.estado }}</span>
              <span class="cita-fila-total">{{ Number(c.costo_total).toFixed(0) }} Bs. · {{ c.metodo_pago }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useCitaPresencialStore } from '../../stores/citaPresencial.js'

const emit = defineEmits(['cerrar'])
const store = useCitaPresencialStore()

const ciYaBuscado      = ref(false)
const intentoContinuar = ref(false)
const hoy = new Date().toISOString().split('T')[0]

const pasos = [
  { n: 1, label: 'Cliente' },
  { n: 2, label: 'Servicios y Horario' },
  { n: 3, label: 'Pago' },
]

const metodosPago = [
  { value: 'Efectivo', label: 'Efectivo', icon: '💵' },
  { value: 'QR',       label: 'QR',       icon: '📱' },
]

const tocado = ref({
  ci: false, nombre1: false, apellido1: false,
  telefono: false, correo: false,
})

// ── Validaciones ───────────────────────────────────────────────────
const errorCI       = computed(() => { const v = String(store.cliente.ci || '').trim(); return v.length > 0 && !/^\d{4,10}$/.test(v) })
const errorNombre   = computed(() => { const v = String(store.cliente.nombre1 || '').trim(); return v.length > 0 && !/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/.test(v) })
const errorApellido = computed(() => { const v = String(store.cliente.apellido1 || '').trim(); return v.length > 0 && !/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/.test(v) })
const errorTelefono = computed(() => { const v = String(store.cliente.telefono || '').replace(/\s/g, ''); return v.length > 0 && !/^\d{8,10}$/.test(v) })
const errorCorreo   = computed(() => { const v = store.cliente.correo || ''; return v.length > 0 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) })

const paso1Valido = computed(() =>
  store.cliente.ci        && !errorCI.value       &&
  store.cliente.nombre1   && !errorNombre.value   &&
  store.cliente.apellido1 && !errorApellido.value &&
  store.cliente.telefono  && !errorTelefono.value &&
  store.cliente.correo    && !errorCorreo.value
)

// ── Slots ─────────────────────────────────────────────────────────
const ahora = computed(() => {
  const esHoy = store.fechaCita === hoy
  if (!esHoy) return null
  const now = new Date()
  const minutos = now.getMinutes()
  const minutosRedondeados = Math.ceil(minutos / 15) * 15
  now.setMinutes(minutosRedondeados, 0, 0)
  return now.getHours() * 60 + now.getMinutes()
})

const slotsDisponibles = computed(() => {
  if (!store.slots || !Array.isArray(store.slots)) return []
  return store.slots.filter(slot => {
    if (!slot.disponible) return false
    if (ahora.value !== null) {
      const [h, m] = slot.hora_inicio.split(':').map(Number)
      const slotMinutos = h * 60 + m
      return slotMinutos >= ahora.value
    }
    return true
  })
})

async function cargarSlotsYautoseleccionar() {
  store.horaInicioSeleccionada = null
  await store.cargarSlots()
  if (slotsDisponibles.value.length > 0) {
    store.horaInicioSeleccionada = slotsDisponibles.value[0].hora_inicio
  }
}

function onToggleServicio(s) {
  store.toggleServicio(s)
  if (store.fechaCita) cargarSlotsYautoseleccionar()
}

function cambiarCategoria(id) {
  store.idCategoriaFiltro = id
  store.cargarServicios()
}

// ── Acciones ───────────────────────────────────────────────────────
async function buscarYmarcar() {
  tocado.value.ci = true
  if (!store.cliente.ci || errorCI.value) return
  await store.buscarCliente()
  ciYaBuscado.value = true
}

function resetearBusqueda() {
  ciYaBuscado.value = false
  if (store.clienteEncontrado) store.clienteEncontrado = false
}

function reiniciarFormularioCompleto() {
  Object.keys(tocado.value).forEach(k => (tocado.value[k] = false))
  intentoContinuar.value = false
  ciYaBuscado.value = false
  store.reiniciar()
}

async function avanzarPaso1() {
  intentoContinuar.value = true
  Object.keys(tocado.value).forEach(k => (tocado.value[k] = true))
  if (!paso1Valido.value) return
  store.irAPaso(2)
  if (!store.fechaCita) store.fechaCita = hoy
}

function volverDesdePago() {
  // Si ya se generó el QR no debería poder retroceder sin cancelar
  if (store.reservaPendiente) return
  store.irAPaso(2)
}

onMounted(() => {
  reiniciarFormularioCompleto()
  store.inicializar()
})

watch(
  [() => store.cliente.correo, () => store.cliente.telefono,
   () => store.cliente.nombre1, () => store.cliente.apellido1, () => store.cliente.ci],
  () => { if (intentoContinuar.value && paso1Valido.value) intentoContinuar.value = false }
)
</script>

<style scoped>
/* ── Layout base (igual que antes) ── */
.cita-wrapper { max-width: 760px; margin: 0 auto; padding: 0 0 2rem; color: var(--color-text-primary); }
.cita-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.btn-volver { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; white-space: nowrap; transition: all 0.2s; }
.btn-volver:hover { border-color: var(--color-gold); color: var(--color-gold); }
.cita-titulo { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; margin: 0 0 0.15rem; }
.cita-sub { font-size: 0.82rem; color: var(--color-text-secondary); margin: 0; }

/* ── Tabs ── */
.tabs-wrap { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); }
.tab-btn { background: transparent; border: none; border-bottom: 2px solid transparent; color: var(--color-text-secondary); padding: 0.65rem 0.25rem; margin-right: 1.25rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.tab-btn:hover { color: var(--color-text-primary); }
.tab-btn.activo { color: var(--color-gold); border-bottom-color: var(--color-gold); }

/* ── Stepper ── */
.stepper { display: flex; align-items: center; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.25rem; }
.step { display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 0; position: relative; }
.step:not(:last-child)::after { content: ''; flex: 1; height: 1px; background: var(--color-border); margin: 0 0.5rem; }
.step.completo:not(:last-child)::after { background: var(--color-gold); }
.step-circulo { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--color-border); display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; flex-shrink: 0; background: var(--color-bg-card); color: var(--color-text-secondary); transition: all 0.2s; }
.step.activo .step-circulo { border-color: var(--color-gold); background: var(--color-gold); color: #1a1a2e; }
.step.completo .step-circulo { border-color: var(--color-gold); background: transparent; color: var(--color-gold); }
.step-label { font-size: 0.72rem; white-space: nowrap; color: var(--color-text-secondary); }
.step.activo .step-label { color: var(--color-text-primary); font-weight: 600; }

/* ── Alertas ── */
.alerta-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.85rem; margin-bottom: 1.25rem; }
.campo-error { font-size: 0.72rem; color: #f87171; margin: 0.15rem 0 0; line-height: 1.4; }
.banner-error-local { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-left: 4px solid #ef4444; color: #f87171; border-radius: 8px; padding: 0.85rem 1.1rem; font-size: 0.82rem; line-height: 1.5; margin-top: 1.25rem; }
.banner-error-local strong { font-weight: 700; }
.aviso-verde { font-size: 0.78rem; color: #4ade80; }
.aviso-gris { font-size: 0.78rem; color: var(--color-text-secondary); padding: 1rem 0; }

/* ── Paso contenido ── */
.paso-contenido { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.75rem; }
.paso-titulo { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700; margin: 0 0 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border); }
.subseccion-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-secondary); margin: 0 0 0.85rem; }

/* ── Formulario ── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.75rem; }
@media (max-width: 560px) { .form-grid { grid-template-columns: 1fr; } }
.campo-grupo { display: flex; flex-direction: column; gap: 0.4rem; }
.campo-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-secondary); }
.campo-input { background: var(--color-bg-input, rgba(255,255,255,0.05)); border: 1px solid var(--color-border); border-radius: 7px; padding: 0.6rem 0.85rem; font-size: 0.875rem; color: var(--color-text-primary); width: 100%; box-sizing: border-box; transition: border-color 0.2s; }
.campo-input:focus { outline: none; border-color: var(--color-gold); }
.campo-input.input-error { border-color: #ef4444 !important; background-color: rgba(239,68,68,0.05) !important; }
.campo-fecha { max-width: 220px; }
.ci-row { display: flex; gap: 0.5rem; }
.ci-row .campo-input { flex: 1; }
.btn-buscar { padding: 0.6rem 1.1rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: opacity 0.2s; }
.btn-buscar:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Filtros y servicios ── */
.filtros-wrap { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1.25rem; }
.filtro-btn { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; padding: 0.35rem 0.9rem; border-radius: 20px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-secondary); cursor: pointer; transition: all 0.2s; }
.filtro-btn:hover, .filtro-btn.activo { background: var(--color-gold); color: #1a1a2e; border-color: var(--color-gold); }
.servicios-lista { display: flex; flex-direction: column; gap: 0.5rem; max-height: 320px; overflow-y: auto; margin-bottom: 1rem; }
.servicio-item { display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1rem; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer; transition: all 0.18s; }
.servicio-item:hover { border-color: var(--color-gold); background: rgba(201,168,76,0.05); }
.servicio-item.seleccionado { border-color: var(--color-gold); background: rgba(201,168,76,0.1); }
.servicio-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--color-border); display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: var(--color-gold); flex-shrink: 0; }
.servicio-item.seleccionado .servicio-check { border-color: var(--color-gold); }
.servicio-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; }
.servicio-nombre { font-size: 0.875rem; font-weight: 600; }
.servicio-meta { font-size: 0.72rem; color: var(--color-text-secondary); }
.servicio-precio { font-family: var(--font-heading); font-size: 0.95rem; font-weight: 700; color: var(--color-gold); white-space: nowrap; }
.resumen-servicios { background: rgba(201,168,76,0.08); border: 1px solid rgba(201,168,76,0.25); border-radius: 8px; padding: 0.75rem 1rem; }
.resumen-fila { display: flex; justify-content: space-between; font-size: 0.85rem; }

/* ── Próximo slot banner ── */
.proximo-slot-banner { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; background: rgba(22,163,74,0.1); border: 1px solid rgba(22,163,74,0.3); border-left: 4px solid #16a34a; border-radius: 8px; padding: 0.75rem 1.1rem; margin-bottom: 1.1rem; }
.proximo-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: #4ade80; }
.proximo-hora { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700; color: #fff; }
.proximo-duracion { font-size: 0.78rem; color: var(--color-text-secondary); }

/* ── Slots ── */
.slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(72px, 1fr)); gap: 0.5rem; margin-bottom: 1.25rem; }
.slot-btn { padding: 0.55rem 0.25rem; border: 1px solid var(--color-border); border-radius: 7px; background: transparent; color: var(--color-text-primary); font-size: 0.8rem; font-weight: 600; cursor: pointer; text-align: center; transition: all 0.18s; }
.slot-btn:hover { border-color: var(--color-gold); color: var(--color-gold); }
.slot-btn.activo { background: var(--color-gold); color: #1a1a2e; border-color: var(--color-gold); }

/* ── Resumen cita ── */
.resumen-cita { border: 1px solid var(--color-border); border-radius: 10px; overflow: hidden; }
.resumen-seccion { padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border); }
.resumen-titulo { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-secondary); font-weight: 700; margin: 0 0 0.4rem; }
.resumen-valor { font-size: 0.9rem; font-weight: 600; margin: 0 0 0.15rem; }
.resumen-meta { font-size: 0.78rem; color: var(--color-text-secondary); margin: 0; }
.resumen-servicio-fila { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.2rem 0; }
.resumen-total { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; background: rgba(201,168,76,0.08); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-secondary); }
.resumen-total strong { font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-gold); }

/* ── Métodos de pago ── */
.metodos-pago { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.metodo-btn { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 0.85rem 1.5rem; border: 2px solid var(--color-border); border-radius: 10px; background: transparent; color: var(--color-text-secondary); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s; min-width: 90px; }
.metodo-btn:hover, .metodo-btn.activo { border-color: var(--color-gold); background: rgba(201,168,76,0.12); color: var(--color-gold); }
.metodo-icon { font-size: 1.4rem; }

/* ── QR ── */
.qr-bloque { margin-top: 1.25rem; text-align: center; }
.qr-titulo { font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; }
.qr-caja { display: inline-flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 1.5rem; border: 1px solid var(--color-border); border-radius: 12px; background: rgba(255,255,255,0.03); }
.qr-placeholder { width: 180px; height: 180px; border: 2px dashed var(--color-border); border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fff; }
.qr-placeholder-icon { font-size: 4rem; color: #1a1a2e; }
.qr-referencia { font-size: 0.72rem; color: var(--color-text-secondary); font-family: monospace; }
.qr-monto { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700; color: var(--color-gold); }
.qr-aviso { font-size: 0.78rem; color: var(--color-text-secondary); margin-top: 0.75rem; }

/* ── Pie de paso ── */
.paso-footer { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border); }
.btn-primario { padding: 0.7rem 1.75rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: opacity 0.2s; }
.btn-primario:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-primario:hover:not(:disabled) { opacity: 0.88; }
.btn-secundario { padding: 0.7rem 1.25rem; background: transparent; color: var(--color-text-secondary); border: 1px solid var(--color-border); border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secundario:hover:not(:disabled) { border-color: var(--color-text-secondary); color: var(--color-text-primary); }
.btn-confirmar { padding: 0.75rem 2rem; background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: opacity 0.2s; }
.btn-confirmar:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-confirmar:hover:not(:disabled) { opacity: 0.88; }

/* ── Éxito ── */
.paso-exito { text-align: center; }
.exito-icono { width: 64px; height: 64px; border-radius: 50%; background: rgba(22,163,74,0.15); border: 2px solid #16a34a; color: #4ade80; font-size: 1.75rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
.exito-titulo { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 700; margin: 0 0 1.75rem; }
.exito-detalle { background: rgba(255,255,255,0.03); border: 1px solid var(--color-border); border-radius: 10px; overflow: hidden; text-align: left; margin-bottom: 1.75rem; }
.detalle-fila { display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--color-border); font-size: 0.875rem; }
.detalle-fila:last-child { border-bottom: none; }
.detalle-label { color: var(--color-text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; }
.detalle-valor { font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.detalle-valor.cobrado { color: #4ade80; }
.metodo-tag { font-size: 0.65rem; padding: 0.2rem 0.5rem; background: rgba(201,168,76,0.15); color: var(--color-gold); border-radius: 4px; font-weight: 700; text-transform: uppercase; }
.exito-acciones { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 2rem 0; }

/* ── Mis Citas ── */
.filtros-fecha { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1.5rem; }
.citas-lista { display: flex; flex-direction: column; gap: 0.6rem; }
.cita-fila { display: grid; grid-template-columns: 130px 1fr auto; gap: 1rem; align-items: center; padding: 0.85rem 1rem; border: 1px solid var(--color-border); border-radius: 8px; }
@media (max-width: 600px) { .cita-fila { grid-template-columns: 1fr; } }
.cita-fila-fecha { display: flex; flex-direction: column; gap: 0.1rem; font-size: 0.8rem; }
.cita-fila-info { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.cita-fila-cliente { font-weight: 600; font-size: 0.875rem; }
.cita-fila-servicios { font-size: 0.75rem; color: var(--color-text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cita-fila-estado { display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem; }
.estado-tag { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.55rem; border-radius: 4px; }
.estado-confirmada { background: rgba(22,163,74,0.15); color: #4ade80; }
.estado-pendiente { background: rgba(234,179,8,0.15); color: #facc15; }
.estado-expirada, .estado-invalidada, .estado-cancelada { background: rgba(239,68,68,0.15); color: #f87171; }
.cita-fila-total { font-size: 0.75rem; color: var(--color-text-secondary); }

/* ── Transiciones ── */
.fade-bloque-enter-active, .fade-bloque-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-bloque-enter-from, .fade-bloque-leave-to { opacity: 0; transform: translateY(-6px); }
</style>