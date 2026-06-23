<template>
  <div class="reservar-raiz">
    <nav class="reservar-nav">
      <router-link to="/inicio" class="reservar-brand">The Lamplight</router-link>
      <span class="reservar-nav-sub">Reserva tu cita</span>
    </nav>

    <main class="reservar-main">
      <div class="reservar-header">
        <p class="eyebrow">Agenda tu cita</p>
        <h1 class="titulo-seccion">Reservar Turno</h1>
      </div>

      <!-- Stepper -->
      <ol class="stepper">
        <li
          v-for="s in pasosLabels"
          :key="s.numero"
          :class="['stepper-item', store.paso === s.numero ? 'activo' : '', store.paso > s.numero ? 'completado' : '']">
          <span class="stepper-num">{{ s.numero }}</span>
          <span class="stepper-label">{{ s.label }}</span>
        </li>
      </ol>

      <p v-if="store.error" class="error-banner">{{ store.error }}</p>

      <transition name="fade-paso" mode="out-in">
        <PasoDatosCliente v-if="store.paso === 1" key="1" />
        <PasoBarbero v-else-if="store.paso === 2" key="2" />
        <PasoServiciosHorario v-else-if="store.paso === 3" key="3" />
        <PasoPago v-else-if="store.paso === 4" key="4" />
      </transition>
    </main>
  </div>
</template>

<script setup>
import { onUnmounted } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'
import PasoDatosCliente from '../../components/forms/PasoDatosCliente.vue'
import PasoBarbero from '../../components/forms/PasoBarbero.vue'
import PasoServiciosHorario from '../../components/forms/PasoServiciosHorario.vue'
import PasoPago from '../../components/forms/PasoPago.vue'

const store = useReservaStore()

const pasosLabels = [
  { numero: 1, label: 'Tus datos' },
  { numero: 2, label: 'Barbero' },
  { numero: 3, label: 'Servicios y horario' },
  { numero: 4, label: 'Pago y confirmación' },
]

onUnmounted(() => {
  store.detenerPollingBarberos()
  store.detenerPollingEstado()
})
</script>

<style scoped>
.reservar-raiz {
  min-height: 100vh;
  background-color: #F4F0E6;
  color: #1a1a2e;
  display: flex;
  flex-direction: column;
}

.reservar-nav {
  background: #1a1a2e;
  padding: 1.25rem 2rem;
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
}

.reservar-brand {
  font-family: var(--font-vintage, serif);
  color: #fff;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-weight: 800;
  font-size: 0.95rem;
}

.reservar-nav-sub {
  color: rgba(201, 168, 76, 0.8);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
}

.reservar-main {
  flex: 1;
  width: 100%;
  max-width: 880px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem 4rem;
}

.reservar-header {
  text-align: center;
  margin-bottom: 2rem;
}

.eyebrow {
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.45em;
  color: #c9a84c;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.titulo-seccion {
  font-family: var(--font-vintage, serif);
  font-size: clamp(2rem, 5vw, 2.75rem);
  font-weight: 900;
  text-transform: uppercase;
}

.stepper {
  display: flex;
  justify-content: space-between;
  list-style: none;
  padding: 0;
  margin: 0 0 2.5rem;
  gap: 0.5rem;
}

.stepper-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  text-align: center;
  opacity: 0.4;
}

.stepper-item.activo,
.stepper-item.completado { opacity: 1; }

.stepper-num {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #1a1a2e;
  font-family: var(--font-vintage, serif);
  font-weight: 800;
  font-size: 0.85rem;
}

.stepper-item.activo .stepper-num {
  background: #1a1a2e;
  color: #F4F0E6;
}

.stepper-item.completado .stepper-num {
  background: #c9a84c;
  border-color: #c9a84c;
  color: #1a1a2e;
}

.stepper-label {
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.error-banner {
  background: #fde8e8;
  border: 1px solid #e0a0a0;
  color: #8a2222;
  padding: 0.85rem 1.1rem;
  font-size: 0.85rem;
  margin-bottom: 1.5rem;
}

.fade-paso-enter-active,
.fade-paso-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.fade-paso-enter-from { opacity: 0; transform: translateY(8px); }
.fade-paso-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
