<template>
  <section class="paso">
    <h2 class="paso-titulo">Elige tu barbero</h2>
    <p class="paso-sub">La disponibilidad se actualiza automáticamente según la agenda del día.</p>

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
        <span :class="['badge-disp', b.disponible_ahora ? 'disponible' : 'ocupado']">
          {{ b.disponible_ahora ? 'Disponible ahora' : 'Ocupado ahora' }}
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
import { onMounted } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()

onMounted(() => {
  store.iniciarPollingBarberos()
})
</script>

<style scoped>
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.75rem; }

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
