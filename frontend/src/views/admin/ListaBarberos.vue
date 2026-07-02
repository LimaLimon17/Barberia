<template>
  <div class="lista animate-fade-in">
    <div class="lista__header">
      <h1 class="lista__title">Gestión de Barberos</h1>
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span class="lista__count" v-if="barberos.length">
          {{ barberos.length }} barbero{{ barberos.length !== 1 ? 's' : '' }}
        </span>
        <router-link to="/admin/barberos/nuevo" class="btn-primary">
          + Registrar barbero
        </router-link>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="lista__loading">
      <div class="lista__spinner"></div>
      <p>Cargando barberos...</p>
    </div>

    <!-- Error -->
    <AlertMessage v-if="error" :mensaje="error" tipo="error" />

    <!-- Confirmación de desactivación -->
    <div v-if="mensajeExito" class="lista__exito">
      {{ mensajeExito }}
    </div>

    <!-- Tabla de barberos -->
    <div v-if="!cargando && barberos.length" class="lista__table-wrapper glass-card">
      <table class="lista__table">
        <thead>
          <tr>
            <th>Barbero</th>
            <th>Correo</th>
            <th>Fecha Ingreso</th>
            <th>Antigüedad</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="barbero in barberos"
            :key="barbero.id_barbero"
            class="lista__row"
          >
            <td>
              <div class="lista__barbero-info">
                <div class="lista__avatar">
                  {{ barbero.nombre_completo.charAt(0) }}
                </div>
                <span class="lista__barbero-name">{{ barbero.nombre_completo }}</span>
              </div>
            </td>
            <td>{{ barbero.correo }}</td>
            <td>{{ formatearFechaCorta(barbero.fecha_ingreso) }}</td>
            <td>
              <span class="lista__antiguedad">{{ barbero.antiguedad_dias }} días</span>
            </td>
            <td>
              <span :class="barbero.estado_activo ? 'badge-active' : 'badge-inactive'">
                ● {{ barbero.estado }}
              </span>
            </td>
            <td>
              <div class="lista__actions">
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}`"
                  class="btn-secondary lista__btn"
                  title="Ver perfil"
                >
                  👁️ Ver
                </router-link>
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}/editar`"
                  class="btn-primary lista__btn"
                  title="Editar"
                >
                  ✏️ Editar
                </router-link>
                <button
                  v-if="barbero.estado_activo"
                  @click="confirmarDesactivar(barbero)"
                  class="lista__btn lista__btn--danger"
                  title="Desactivar"
                >
                  🚫 Desactivar
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Sin barberos -->
    <div v-if="!cargando && !barberos.length && !error" class="lista__empty glass-card">
      <p>No hay barberos registrados en el sistema.</p>
    </div>

    <!-- Modal de confirmación -->
    <div v-if="barberoADesactivar" class="lista__modal-overlay" @click.self="barberoADesactivar = null">
      <div class="lista__modal">
        <h3 class="lista__modal-title">¿Desactivar barbero?</h3>
        <p class="lista__modal-texto">
          Estás por desactivar a <strong>{{ barberoADesactivar.nombre_completo }}</strong>.
          Este barbero no podrá iniciar sesión ni aparecerá en la agenda.
        </p>
        <div class="lista__modal-acciones">
          <button
            @click="barberoADesactivar = null"
            class="btn-secondary"
          >
            Cancelar
          </button>
          <button
            @click="desactivar"
            :disabled="desactivando"
            class="lista__btn--danger lista__btn--danger-full"
          >
            {{ desactivando ? 'Desactivando...' : 'Sí, desactivar' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { formatearFechaCorta } from '../../utils/helpers.js'

const barberos          = ref([])
const cargando          = ref(true)
const error             = ref('')
const mensajeExito      = ref('')
const barberoADesactivar = ref(null)
const desactivando      = ref(false)

onMounted(async () => {
  await cargarBarberos()
})

async function cargarBarberos() {
  cargando.value = true
  error.value    = ''
  try {
    const data = await barberoService.getAll()
    barberos.value = data.barberos
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar los barberos'
  } finally {
    cargando.value = false
  }
}

function confirmarDesactivar(barbero) {
  barberoADesactivar.value = barbero
}

async function desactivar() {
  if (!barberoADesactivar.value) return
  desactivando.value = true
  try {
    await barberoService.desactivar(barberoADesactivar.value.id_barbero)
    mensajeExito.value = `${barberoADesactivar.value.nombre_completo} fue desactivado correctamente.`
    barberoADesactivar.value = null
    await cargarBarberos()
    setTimeout(() => { mensajeExito.value = '' }, 4000)
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al desactivar el barbero'
    barberoADesactivar.value = null
  } finally {
    desactivando.value = false
  }
}
</script>

<style scoped>
.lista {
  width: 100%;
  max-width: 1200px; /* Ajusta según tu necesidad de diseño */
  margin: 0;         /* Elimina el 'auto' que genera el centrado */
  padding: 0 2rem;   /* Espaciado lateral para que no pegue contra el sidebar */
}

.lista__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.lista__title {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 700;
}

.lista__count {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  background: var(--color-bg-card);
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border);
}

.lista__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.lista__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold-400);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.lista__table-wrapper {
  padding: 0;
  overflow-x: auto;
}

.lista__table {
  width: 100%;
  border-collapse: collapse;
}

.lista__table th {
  text-align: center;
  padding: 0.875rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 700; 
  color: #000000; /* <-- Cambiado aquí para que sea negro */
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-bg-secondary);
}

.lista__table th:first-child { border-radius: var(--radius-xl) 0 0 0; }
.lista__table th:last-child  { border-radius: 0 var(--radius-xl) 0 0; }

.lista__table td {
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  color: var(--color-text-primary);
  border-bottom: 1px solid var(--color-border);
}

.lista__row { transition: background 0.2s ease; }
.lista__row:hover { background: var(--color-bg-hover); }
.lista__row:last-child td { border-bottom: none; }

.lista__barbero-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.lista__avatar {
  width: 30px;         /* Tamaño compacto idéntico a Horarios */
  height: 30px;        /* Mantener proporción perfecta */
  border-radius: 50%;  /* Círculo perfecto */
  background: linear-gradient(135deg, var(--color-bronce), var(--color-oro-suave)); /* Gradiente premium */
  color: var(--color-azul-oscuro); /* Contraste elegante y legible */
  display: flex;
  align-items: center;     /* Centrado vertical */
  justify-content: center; /* Centrado horizontal */
  font-family: var(--font-heading);
  font-weight: 700;        /* Inicial marcada */
  font-size: 0.75rem;      /* Escala proporcional al tamaño del círculo */
  flex-shrink: 0;          /* Evita deformaciones */
}

.lista__barbero-name {
  font-size: 0.875rem;
  color: var(--color-text-primary);
}

.lista__antiguedad {
  color: var(--color-gold-400);
  font-weight: 600;
}

.lista__actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.lista__btn {
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
}

.lista__btn--danger {
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background 0.2s;
}

.lista__btn--danger:hover {
  background: #fef2f2;
}

.lista__btn--danger-full {
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  background: #ef4444;
  border: none;
  color: white;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background 0.2s;
}

.lista__btn--danger-full:hover   { background: #dc2626; }
.lista__btn--danger-full:disabled { opacity: 0.5; cursor: not-allowed; }

.lista__empty {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
}

.lista__exito {
  margin-bottom: 1rem;
  padding: 0.875rem 1.25rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: var(--radius-lg);
  color: #15803d;
  font-size: 0.875rem;
}

.lista__modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.lista__modal {
  background: var(--color-bg-card);
  border-radius: var(--radius-xl);
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.lista__modal-title {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.lista__modal-texto {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.lista__modal-acciones {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}
</style>
