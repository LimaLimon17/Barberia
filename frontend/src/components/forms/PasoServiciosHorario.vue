<template>
  <section class="paso">
    <h2 class="paso-titulo">Servicios y horario</h2>
    <p class="paso-sub">Selecciona uno o más servicios, la fecha y un horario disponible.</p>

    <!-- Filtro categoría -->
    <div class="filtros-wrap">
      <button
        :class="['filtro-btn', !store.idCategoriaFiltro ? 'activo' : '']"
        @click="cambiarCategoria('')">
        Todo
      </button>
      <button
        v-for="cat in categoriasDisponibles"
        :key="cat"
        :class="['filtro-btn', store.idCategoriaFiltro === cat ? 'activo' : '']"
        @click="cambiarCategoria(cat)">
        Categoría {{ cat }}
      </button>
    </div>

    <!-- Servicios -->
    <div v-if="store.cargandoServicios" class="lista-servicios">
      <div v-for="n in 4" :key="n" class="skeleton-servicio"></div>
    </div>
    <div v-else class="lista-servicios">
      <label
        v-for="s in store.servicios"
        :key="s.IdServicio"
        :class="['item-servicio', estaSeleccionado(s) ? 'seleccionado' : '']">
        <input type="checkbox" :checked="estaSeleccionado(s)" @change="onToggle(s)" />
        <span class="info-servicio">
          <span class="nombre-servicio">{{ s.Nombre }}</span>
          <span class="meta-servicio">{{ s.DuracionMinutos }} min · {{ Number(s.Precio).toFixed(0) }} Bs.</span>
        </span>
      </label>
    </div>

    <!-- Resumen de duración / costo -->
    <div v-if="store.serviciosSeleccionados.length" class="resumen-bloque">
      <span>Duración estimada del bloque: <strong>{{ store.duracionBloqueEstimada }} min</strong> (incluye 10 min de limpieza + 5 min de tolerancia)</span>
      <span>Costo total: <strong>{{ store.costoTotal.toFixed(0) }} Bs.</strong></span>
    </div>

    <!-- Fecha -->
    <div class="campo campo-fecha">
      <label>Fecha de la cita</label>
      <input v-model="store.fechaCita" type="date" :min="hoy" @change="store.cargarSlotsDisponibles()" />
    </div>

    <!-- Horarios -->
    <div v-if="store.fechaCita && store.serviciosSeleccionados.length" class="horarios-wrap">
      <p class="horarios-titulo">Horarios disponibles</p>
      <div v-if="store.cargandoSlots" class="aviso-info">Calculando horarios…</div>
      <div v-else-if="store.slots.length === 0" class="aviso-info">
        No hay horarios disponibles para esta combinación de servicios en esa fecha.
      </div>
      <div v-else class="grid-slots">
        <button
          v-for="slot in store.slots"
          :key="slot.hora_inicio"
          :disabled="!slot.disponible"
          :class="['slot-btn', !slot.disponible ? 'ocupado' : '', store.horaInicioSeleccionada === slot.hora_inicio ? 'seleccionado' : '']"
          @click="store.seleccionarHorario(slot.hora_inicio)">
          {{ slot.hora_inicio }}
        </button>
      </div>
    </div>

    <!-- ⚠ Advertencia: aparece solo al seleccionar un slot -->
    <transition name="fade-advertencia">
      <div v-if="store.horaInicioSeleccionada" class="advertencia-horario">
        <span class="advertencia-icono">⚠</span>
        <div class="advertencia-texto">
          <strong>Antes de continuar</strong>
          <p>
            Una vez que procedas al pago, <em>el horario seleccionado ({{ store.horaInicioSeleccionada }}) no podrá modificarse</em>.
            Asegurate de que el barbero, los servicios y el turno sean los correctos.
          </p>
        </div>
      </div>
    </transition>

    <div class="acciones">
      <button class="btn-secundario" @click="store.irAPaso(2)">← Volver</button>
      <button class="btn-primario" :disabled="!puedeContinuar" @click="store.confirmarReserva()">
        Confirmar y pagar →
      </button>
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed, watch } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()
const hoy = new Date().toISOString().slice(0, 10)

onMounted(() => {
  store.cargarServicios()
})

const categoriasDisponibles = computed(() =>
  [...new Set(store.servicios.map((s) => s.IdCategoria))]
)

function cambiarCategoria(cat) {
  store.idCategoriaFiltro = cat
  store.cargarServicios()
}

function estaSeleccionado(servicio) {
  return store.serviciosSeleccionados.some((s) => s.IdServicio === servicio.IdServicio)
}

function onToggle(servicio) {
  store.toggleServicio(servicio)
  store.cargarSlotsDisponibles()
}

watch(() => store.idBarberoSeleccionado, () => store.cargarSlotsDisponibles())

const puedeContinuar = computed(
  () =>
    store.serviciosSeleccionados.length > 0 &&
    store.fechaCita &&
    store.horaInicioSeleccionada
)
</script>

<style scoped>
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.5rem; }

.filtros-wrap { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
.filtro-btn {
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
  padding: 0.5rem 1rem;
  border: 1px solid #d5d0c6;
  background: transparent;
  cursor: pointer;
}
.filtro-btn.activo { background: #1a1a2e; color: #F4F0E6; border-color: #1a1a2e; }

.lista-servicios { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
.item-servicio {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border: 1px solid #d5d0c6;
  padding: 0.85rem 1rem;
  cursor: pointer;
}
.item-servicio.seleccionado { border-color: #c9a84c; box-shadow: 0 0 0 1px #c9a84c inset; }
.info-servicio { display: flex; flex-direction: column; gap: 0.15rem; }
.nombre-servicio { font-weight: 700; font-size: 0.85rem; }
.meta-servicio { font-size: 0.72rem; color: #888; }
.skeleton-servicio { height: 60px; background: #ede9df; animation: pulse 1.4s ease-in-out infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

.resumen-bloque {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  background: #f8f5ef;
  border: 1px solid #e8e4da;
  padding: 0.9rem 1.1rem;
  font-size: 0.82rem;
  margin-bottom: 1.5rem;
}

.campo { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.5rem; }
.campo label {
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #c9a84c;
  font-weight: 700;
}
.campo-fecha input { border: 1px solid #d5d0c6; padding: 0.7rem 0.85rem; font-size: 0.9rem; max-width: 220px; }

.horarios-wrap { margin-bottom: 1.5rem; }
.horarios-titulo { font-family: var(--font-vintage, serif); font-size: 0.8rem; font-weight: 800; margin-bottom: 0.75rem; }
.grid-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.5rem; }
.slot-btn {
  border: 1px solid #d5d0c6;
  background: #fff;
  padding: 0.5rem 0.3rem;
  font-size: 0.78rem;
  cursor: pointer;
}
.slot-btn.seleccionado { background: #1a1a2e; color: #F4F0E6; border-color: #1a1a2e; }
.slot-btn.ocupado { opacity: 0.3; text-decoration: line-through; cursor: not-allowed; }

.aviso-info { font-size: 0.78rem; color: #8a6d1a; }

/* Advertencia */
.advertencia-horario {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  background: #fffbeb;
  border: 1px solid #c9a84c;
  border-left: 4px solid #c9a84c;
  padding: 0.9rem 1.1rem;
  margin-bottom: 1.5rem;
}
.advertencia-icono {
  font-size: 1.1rem;
  color: #c9a84c;
  flex-shrink: 0;
  line-height: 1.4;
}
.advertencia-texto strong {
  display: block;
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #1a1a2e;
  margin-bottom: 0.3rem;
}
.advertencia-texto p {
  font-size: 0.8rem;
  color: #5a4a1a;
  margin: 0;
  line-height: 1.55;
}
.advertencia-texto em {
  font-style: normal;
  font-weight: 700;
  color: #1a1a2e;
}

/* Transición suave */
.fade-advertencia-enter-active,
.fade-advertencia-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-advertencia-enter-from,
.fade-advertencia-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.acciones { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
.btn-primario, .btn-secundario {
  font-family: var(--font-vintage, serif);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-weight: 700;
  padding: 0.75rem 1.5rem;
  border: none;
  cursor: pointer;
}
.btn-primario { background: #1a1a2e; color: #F4F0E6; }
.btn-primario:hover { background: #c9a84c; color: #1a1a2e; }
.btn-primario:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; }
</style>