<template>
  <section class="paso">
    <h2 class="paso-titulo">Elige tu barbero</h2>
    <p class="paso-sub">La disponibilidad se actualiza automáticamente según la agenda del día.</p>

    <!-- Aviso global: la barbería está cerrada en este momento -->
    <div v-if="barberiaCerrada" class="aviso-cerrado">
      <span class="aviso-cerrado-icono">🕙</span>
      <div>
        <strong>La barbería está cerrada en este momento</strong>
        <p>Atendemos de 10:00 a 22:00. Puedes seguir reservando para más adelante, pero ningún barbero está disponible justo ahora.</p>
      </div>
    </div>

    <div v-if="store.cargandoBarberos && store.barberos.length === 0" class="lista-barberos">
      <div v-for="n in 3" :key="n" class="skeleton-barbero"></div>
    </div>

    <div v-else class="lista-barberos">
      <button
        v-for="b in store.barberos"
        :key="b.id_barbero"
        :class="['card-barbero', store.idBarberoSeleccionado === b.id_barbero ? 'seleccionado' : '']"
        @click="store.seleccionarBarbero(b.id_barbero)">
        <span class="nombre-barbero">{{ b.nombre }}</span>
        <span :class="['badge-disp', claseBadge(b.estado)]">
          {{ textoBadge(b.estado) }}
        </span>
      </button>
    </div>

    <p v-if="!store.cargandoBarberos && store.barberos.length === 0" class="aviso-info">
      No hay barberos activos en este momento.
    </p>

    <div class="acciones">
      <button class="btn-secundario" @click="store.irAPaso(1)">← Volver</button>
      <button class="btn-primario" :disabled="!store.idBarberoSeleccionado" @click="store.irAPaso(3)">
        Continuar →
      </button>
    </div>
  </section>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()

onMounted(() => {
  store.iniciarPollingBarberos()
})

// Si TODOS los barberos cargados están "fuera_de_horario", es la barbería
// entera la que está cerrada (aplica a todos por igual).
const barberiaCerrada = computed(() =>
  store.barberos.length > 0 && store.barberos.every((b) => b.estado === 'fuera_de_horario')
)

function textoBadge(estado) {
  switch (estado) {
    case 'disponible':        return 'Disponible ahora'
    case 'ocupado':           return 'Ocupado ahora'
    case 'descanso':          return 'En descanso hoy'
    case 'fuera_de_horario':  return 'Fuera de horario'
    default:                  return estado ? estado : 'Sin información'
  }
}

function claseBadge(estado) {
  switch (estado) {
    case 'disponible':        return 'disponible'
    case 'ocupado':           return 'ocupado'
    case 'descanso':          return 'descanso'
    case 'fuera_de_horario':  return 'fuera-horario'
    default:                  return 'ocupado'
  }
}
</script>

<style scoped>
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.75rem; }

/* Aviso global de cierre */
.aviso-cerrado {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  background: #f3eee0;
  border: 1px solid #c9a84c;
  border-left: 4px solid #c9a84c;
  padding: 0.9rem 1.1rem;
  margin-bottom: 1.5rem;
}
.aviso-cerrado-icono { font-size: 1.1rem; flex-shrink: 0; line-height: 1.4; }
.aviso-cerrado strong {
  display: block;
  font-family: var(--font-vintage, serif);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #1a1a2e;
  margin-bottom: 0.25rem;
}
.aviso-cerrado p { font-size: 0.8rem; color: #5a4a1a; margin: 0; line-height: 1.5; }

.lista-barberos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.card-barbero {
  border: 1px solid #d5d0c6;
  background: #fff;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  cursor: pointer;
  text-align: left;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.card-barbero:hover { border-color: #1a1a2e; }
.card-barbero.seleccionado { border-color: #c9a84c; box-shadow: 0 0 0 1px #c9a84c inset; }

.nombre-barbero {
  font-family: var(--font-vintage, serif);
  font-weight: 800;
  font-size: 1rem;
  color: #1a1a2e;
}

.badge-disp {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  width: fit-content;
}
.badge-disp.disponible { background: #e3f3e6; color: #2f7a3e; }
.badge-disp.ocupado { background: #f3e6e6; color: #8a2222; }
.badge-disp.descanso { background: #e6e9f3; color: #2f3f8a; }
.badge-disp.fuera-horario { background: #ece6da; color: #6b5a2e; }

.skeleton-barbero { height: 92px; background: #ede9df; animation: pulse 1.4s ease-in-out infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

.aviso-info { font-size: 0.78rem; color: #8a6d1a; margin-bottom: 1rem; }

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
  transition: all 0.2s;
}
.btn-primario { background: #1a1a2e; color: #F4F0E6; }
.btn-primario:hover { background: #c9a84c; color: #1a1a2e; }
.btn-primario:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; }
</style>