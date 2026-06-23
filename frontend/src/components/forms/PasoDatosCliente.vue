<template>
  <section class="paso">
    <h2 class="paso-titulo">Tus datos</h2>
    <p class="paso-sub">Ingresa tu CI para autocompletar tus datos si ya nos visitaste antes.</p>

    <div class="campo">
      <label>Cédula de identidad</label>
      <div class="fila-ci">
        <input v-model="ciInput" type="text" placeholder="Ej: 1000001" />
        <button class="btn-secundario" :disabled="!ciInput || store.buscandoCliente" @click="buscar">
          {{ store.buscandoCliente ? 'Buscando…' : 'Buscar' }}
        </button>
      </div>
      <p v-if="busquedaRealizada && store.clienteEncontrado" class="aviso-ok">
        Te encontramos, revisamos tus datos abajo.
      </p>
      <p v-else-if="busquedaRealizada && !store.clienteEncontrado" class="aviso-info">
        No tenemos registros con esa CI, completa tus datos manualmente.
      </p>
    </div>

    <div class="grid-2">
      <div class="campo">
        <label>Nombre</label>
        <input v-model="store.cliente.Nombre1" type="text" />
      </div>
      <div class="campo">
        <label>Apellido</label>
        <input v-model="store.cliente.Apellido1" type="text" />
      </div>
      <div class="campo">
        <label>Teléfono</label>
        <input v-model="store.cliente.Telefono" type="text" inputmode="numeric" />
      </div>
      <div class="campo">
        <label>Correo electrónico</label>
        <input v-model="store.cliente.Correo" type="email" />
      </div>
    </div>

    <p v-if="camposFaltantes.length" class="aviso-error">
      Completa: {{ camposFaltantes.join(', ') }}
    </p>

    <div class="acciones">
      <span></span>
      <button class="btn-primario" @click="siguiente">Continuar →</button>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()
const ciInput = ref(store.cliente.CI || '')
const busquedaRealizada = ref(false)

async function buscar() {
  await store.buscarClientePorCI(ciInput.value)
  busquedaRealizada.value = true
}

const camposFaltantes = computed(() => {
  const faltan = []
  if (!store.cliente.CI) faltan.push('CI')
  if (!store.cliente.Nombre1) faltan.push('Nombre')
  if (!store.cliente.Apellido1) faltan.push('Apellido')
  if (!store.cliente.Telefono) faltan.push('Teléfono')
  if (!store.cliente.Correo) faltan.push('Correo')
  return faltan
})

function siguiente() {
  store.cliente.CI = ciInput.value
  if (camposFaltantes.value.length > 0) return
  store.irAPaso(2)
}
</script>

<style scoped>
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.75rem; }
.campo { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
.campo label {
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #c9a84c;
  font-weight: 700;
}
.campo input {
  border: 1px solid #d5d0c6;
  padding: 0.7rem 0.85rem;
  font-size: 0.9rem;
  background: #fff;
}
.fila-ci { display: flex; gap: 0.6rem; }
.fila-ci input { flex: 1; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.25rem; }
@media (max-width: 560px) { .grid-2 { grid-template-columns: 1fr; } }

.aviso-ok { font-size: 0.78rem; color: #2f7a3e; }
.aviso-info { font-size: 0.78rem; color: #8a6d1a; }
.aviso-error { font-size: 0.8rem; color: #8a2222; margin-bottom: 1rem; }

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
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; white-space: nowrap; }
.btn-secundario:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
