<template>
  <div class="dashboard animate-fade-in">

    <!-- ── Vista principal del dashboard ─────────────────────── -->
    <template v-if="vista === 'inicio'">
      <div class="dashboard__welcome">
        <h1 class="dashboard__greeting">
          Hola, <span class="gold-text">{{ authStore.usuario?.nombre1 }}</span> 👋
        </h1>
        <p class="dashboard__date">{{ fechaHoy }}</p>
      </div>

      <div class="dashboard__cards">
        <div
          class="dashboard__card glass-card dashboard__card--accionable"
          @click="vista = 'agenda'"
        >
          <div class="dashboard__card-icon">📅</div>
          <div class="dashboard__card-info">
            <h3 class="dashboard__card-title">Mi Agenda</h3>
            <p class="dashboard__card-desc">Consulta y gestiona tus citas del día</p>
          </div>
        </div>

        <div
          class="dashboard__card glass-card dashboard__card--accionable"
          @click="vista = 'cita-presencial'"
        >
          <div class="dashboard__card-icon">✂️</div>
          <div class="dashboard__card-info">
            <h3 class="dashboard__card-title">Crear Cita</h3>
            <p class="dashboard__card-desc">Registrar cliente presencial ahora</p>
          </div>
        </div>

        <div
  class="dashboard__card glass-card dashboard__card--accionable"
  @click="vista = 'comisiones'"
>
  <div class="dashboard__card-icon">💰</div>
  <div class="dashboard__card-info">
    <h3 class="dashboard__card-title">Comisiones</h3>
    <p class="dashboard__card-desc">Revisa tus ganancias semanales</p>
  </div>
</div>
        <div
  class="dashboard__card glass-card dashboard__card--accionable"
  @click="vista = 'venta-directa'"
>
  <div class="dashboard__card-icon">🛍️</div>
  <div class="dashboard__card-info">
    <h3 class="dashboard__card-title">Venta sin cita</h3>
    <p class="dashboard__card-desc">Vender productos a un cliente ocasional</p>
  </div>
</div>

        <router-link to="/barbero/perfil" class="dashboard__card glass-card dashboard__card--link" id="btn-ver-perfil">
          <div class="dashboard__card-icon">👤</div>
          <div class="dashboard__card-info">
            <h3 class="dashboard__card-title">Visualizar Perfil</h3>
            <p class="dashboard__card-desc">Ver tu información personal y antigüedad</p>
          </div>
          <span class="dashboard__card-arrow">→</span>
        </router-link>
      </div>
    </template>

    <!-- ── Formulario crear cita presencial ─────────────────── -->
    <template v-else-if="vista === 'cita-presencial'">
      <CrearCitaPresencial @cerrar="vista = 'inicio'" />
    </template>

    <template v-else-if="vista === 'agenda'">
      <AgendaCitas @cerrar="vista = 'inicio'" />
    </template>
    <template v-else-if="vista === 'venta-directa'">
  <VentaDirecta @cerrar="vista = 'inicio'" />
</template>
<template v-else-if="vista === 'comisiones'">
  <ComisionesSemana @cerrar="vista = 'inicio'" />
</template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import CrearCitaPresencial from './Crearcitapresencial.vue'
import AgendaCitas from './AgendaCitas.vue'
import VentaDirecta from './VentaDirecta.vue'
import ComisionesSemana from './ComisionesSemana.vue'

const authStore = useAuthStore()
const route = useRoute()
const vista = ref(route.query.vista || 'inicio')

watch(() => route.query.vista, (nuevoValor) => {
  vista.value = nuevoValor || 'inicio'
})

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
)
</script>


<style scoped>
.dashboard {
  max-width: 1100px;
}

.dashboard__welcome {
  margin-bottom: 2rem;
}

.dashboard__greeting {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.dashboard__date {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  text-transform: capitalize;
}

.dashboard__cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.dashboard__card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  cursor: default;
  transition: all 0.3s ease;
  text-decoration: none;
  color: inherit;
}

.dashboard__card--link {
  position: relative;
  border: 2px solid transparent;
}

.dashboard__card--link:hover {
  border-color: var(--color-azul-real);
}

.dashboard__card--accionable {
  cursor: pointer;
}

.dashboard__card--accionable:hover {
  background: var(--color-bg-hover);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  border-color: var(--color-gold) !important;
}

.dashboard__card:hover:not(.dashboard__card--accionable) {
  background: var(--color-bg-hover);
  box-shadow: 0 8px 20px rgba(13, 27, 42, 0.1);
}

.dashboard__card-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.dashboard__card-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 0.125rem;
}

.dashboard__card-desc {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
}

.card-badge {
  position: absolute;
  top: 0.6rem;
  right: 0.75rem;
  background: var(--color-gold);
  color: #1a1a2e;
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
} /* <-- CORREGIDO: Llave de cierre añadida */

.dashboard__card-arrow {
  margin-left: auto;
  font-size: 1.25rem;
  color: var(--color-azul-real);
  font-weight: 700;
  transition: transform 0.2s ease;
}

.dashboard__card--link:hover .dashboard__card-arrow {
  transform: translateX(4px);
}

.dashboard__section {
  padding: 1.5rem;
}

.dashboard__section-title {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.dashboard__empty {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
</style>