<template>
  <div class="lista animate-fade-in">
    <div class="lista__header">
      <h1 class="lista__title">Gestión de Barberos</h1>
      <router-link to="/admin/barberos/nuevo" class="btn-primary">
        + Registrar barbero
      </router-link>
    </div>

    <AlertMessage v-if="error" :mensaje="error" tipo="error" />
    <div v-if="mensajeExito" class="lista__exito">{{ mensajeExito }}</div>

    <!-- ── Buscador ── -->
    <div class="lista__filtros glass-card">
      <div class="lista__filtro-grupo">
        <label class="lista__filtro-label">Nombre o correo</label>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar por nombre o correo..."
          class="lista__input"
        />
      </div>
      <div class="lista__filtro-grupo">
        <label class="lista__filtro-label">Estado</label>
        <select v-model="filtroEstado" class="lista__input">
          <option value="todos">Todos</option>
          <option value="activos">Solo activos</option>
          <option value="inactivos">Solo inactivos</option>
        </select>
      </div>
      <button class="btn-secondary lista__btn-limpiar" @click="limpiarFiltros"
        v-if="busqueda || filtroEstado !== 'todos'">
        🧹 Limpiar
      </button>
    </div>

    <div v-if="cargando" class="lista__loading">
      <div class="lista__spinner"></div>
      <p>Cargando barberos...</p>
    </div>

    <!-- Sección activos: se oculta si el filtro es 'inactivos' -->
<template v-if="filtroEstado !== 'inactivos'">
  <div class="lista__seccion-titulo">
    <span>Barberos activos</span>
    <span class="lista__count">{{ barberosActivosFiltrados.length }}</span>
  </div>

  <div v-if="barberosActivosFiltrados.length" class="lista__table-wrapper glass-card">
            <table class="lista__table">
          <thead>
            <tr>
              <th>Barbero</th>
              <th>Correo</th>
              <th>Fecha Ingreso</th>
              <th>Antigüedad</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="barbero in barberosActivosFiltrados" :key="barbero.id_barbero" class="lista__row">
              <td>
                <div class="lista__barbero-info">
                  <div class="lista__avatar">{{ barbero.nombre_completo.charAt(0) }}</div>
                  <span>{{ barbero.nombre_completo }}</span>
                </div>
              </td>
              <td>{{ barbero.correo }}</td>
              <td>{{ formatearFechaCorta(barbero.fecha_ingreso) }}</td>
              <td><span class="lista__antiguedad">{{ barbero.antiguedad_dias }} días</span></td>
              <td>
                <div class="lista__actions">
                  <router-link :to="`/admin/barberos/${barbero.id_barbero}`" class="btn-secondary lista__btn">
                    👁️ Ver
                  </router-link>
                  <router-link :to="`/admin/barberos/${barbero.id_barbero}/editar`" class="btn-primary lista__btn">
                    ✏️ Editar
                  </router-link>
                  <button @click="confirmarDesactivar(barbero)" class="lista__btn lista__btn--danger">
                    🚫 Desactivar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  <div v-else class="lista__empty glass-card">
    <p>No hay barberos activos que coincidan con la búsqueda.</p>
  </div>
</template>


      <!-- ── Barberos inactivos ── -->
      
<!-- Sección inactivos: se oculta si el filtro es 'activos' -->
<template v-if="filtroEstado !== 'activos'">
  <div class="lista__seccion-titulo lista__seccion-titulo--inactivos" style="margin-top: 2rem;">
    <span>Barberos inactivos</span>
    <span class="lista__count lista__count--inactivo">{{ barberosInactivosFiltrados.length }}</span>
  </div>

  <div v-if="barberosInactivosFiltrados.length" class="lista__table-wrapper glass-card lista__table-wrapper--inactivo">
    <table class="lista__table">
            <thead>
              <tr>
                <th>Barbero</th>
                <th>Correo</th>
                <th>Fecha Ingreso</th>
                <th>Antigüedad</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="barbero in barberosInactivosFiltrados" :key="barbero.id_barbero"
                class="lista__row lista__row--inactivo">
                <td>
                  <div class="lista__barbero-info">
                    <div class="lista__avatar lista__avatar--inactivo">{{ barbero.nombre_completo.charAt(0) }}</div>
                    <span>{{ barbero.nombre_completo }}</span>
                  </div>
                </td>
                <td>{{ barbero.correo }}</td>
                <td>{{ formatearFechaCorta(barbero.fecha_ingreso) }}</td>
                <td><span class="lista__antiguedad">{{ barbero.antiguedad_dias }} días</span></td>
                <td>
                  <div class="lista__actions">
                    <router-link :to="`/admin/barberos/${barbero.id_barbero}`" class="btn-secondary lista__btn">
                      👁️ Ver
                    </router-link>
                    <button @click="confirmarReactivar(barbero)" class="lista__btn lista__btn--reactivar">
                      ✅ Reactivar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
  </div>
  <div v-else class="lista__empty glass-card">
    <p>No hay barberos inactivos{{ busqueda ? ' que coincidan con la búsqueda' : '' }}.</p>
  </div>
</template>

    

    <!-- ── Modal desactivar ── -->
    <div v-if="barberoADesactivar" class="lista__modal-overlay" @click.self="barberoADesactivar = null">
      <div class="lista__modal">
        <h3 class="lista__modal-title">¿Desactivar barbero?</h3>
        <p class="lista__modal-texto">
          Estás por desactivar a <strong>{{ barberoADesactivar.nombre_completo }}</strong>.
          Este barbero no podrá iniciar sesión ni aparecerá en la agenda.
        </p>
        <div class="lista__modal-acciones">
          <button @click="barberoADesactivar = null" class="btn-secondary">Cancelar</button>
          <button @click="desactivar" :disabled="procesando" class="lista__btn--danger lista__btn--danger-full">
            {{ procesando ? 'Desactivando...' : 'Sí, desactivar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Modal reactivar ── -->
    <div v-if="barberoAReactivar" class="lista__modal-overlay" @click.self="barberoAReactivar = null">
      <div class="lista__modal">
        <h3 class="lista__modal-title">¿Reactivar barbero?</h3>
        <p class="lista__modal-texto">
          Estás por reactivar a <strong>{{ barberoAReactivar.nombre_completo }}</strong>.
          Volverá a aparecer en la agenda y podrá iniciar sesión.
        </p>
        <div class="lista__modal-acciones">
          <button @click="barberoAReactivar = null" class="btn-secondary">Cancelar</button>
          <button @click="reactivar" :disabled="procesando" class="lista__btn--reactivar-full">
            {{ procesando ? 'Reactivando...' : 'Sí, reactivar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { formatearFechaCorta } from '../../utils/helpers.js'

const barberos           = ref([])
const cargando           = ref(true)
const error              = ref('')
const mensajeExito       = ref('')
const procesando         = ref(false)
const barberoADesactivar = ref(null)
const barberoAReactivar  = ref(null)

const busqueda    = ref('')
const filtroEstado = ref('todos')

// ── Filtros computados ──────────────────────────────────────────
const coincide = (b) => {
  if (!busqueda.value) return true
  const q = busqueda.value.toLowerCase()
  return (
    b.nombre_completo.toLowerCase().includes(q) ||
    b.correo.toLowerCase().includes(q)
  )
}

const barberosActivosFiltrados = computed(() => {
  if (filtroEstado.value === 'inactivos') return []
  return barberos.value.filter(b => b.estado_activo && coincide(b))
})

const barberosInactivosFiltrados = computed(() => {
  if (filtroEstado.value === 'activos') return []
  return barberos.value.filter(b => !b.estado_activo && coincide(b))
})
function limpiarFiltros() {
  busqueda.value = ''
  filtroEstado.value = 'todos'
}

// ── Carga ───────────────────────────────────────────────────────
onMounted(cargarBarberos)

async function cargarBarberos() {
  cargando.value = true
  error.value = ''
  try {
    const data = await barberoService.getAll()
    barberos.value = data.barberos
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar los barberos'
  } finally {
    cargando.value = false
  }
}

function mostrarExito(msg) {
  mensajeExito.value = msg
  setTimeout(() => { mensajeExito.value = '' }, 4000)
}

// ── Desactivar ──────────────────────────────────────────────────
function confirmarDesactivar(barbero) {
  barberoADesactivar.value = barbero
  error.value = ''
}

async function desactivar() {
  if (!barberoADesactivar.value) return
  procesando.value = true
  try {
    await barberoService.desactivar(barberoADesactivar.value.id_barbero)
    mostrarExito(`${barberoADesactivar.value.nombre_completo} fue desactivado correctamente.`)
    barberoADesactivar.value = null
    await cargarBarberos()
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al desactivar el barbero'
    barberoADesactivar.value = null
  } finally {
    procesando.value = false
  }
}

// ── Reactivar ───────────────────────────────────────────────────
function confirmarReactivar(barbero) {
  barberoAReactivar.value = barbero
  error.value = ''
}

async function reactivar() {
  if (!barberoAReactivar.value) return
  procesando.value = true
  try {
    await barberoService.reactivar(barberoAReactivar.value.id_barbero)
    mostrarExito(`${barberoAReactivar.value.nombre_completo} fue reactivado correctamente.`)
    barberoAReactivar.value = null
    await cargarBarberos()
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al reactivar el barbero'
    barberoAReactivar.value = null
  } finally {
    procesando.value = false
  }
}
</script>

<style scoped>
.lista { max-width: 1100px; }
.lista__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.lista__title { font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; }

.lista__filtros {
  display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap;
  padding: 1.1rem 1.5rem; margin-bottom: 1.5rem;
}
.lista__filtro-grupo { display: flex; flex-direction: column; gap: 0.3rem; min-width: 200px; }
.lista__filtro-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-secondary); }
.lista__input { background: var(--color-bg-primary); border: 1px solid var(--color-border); border-radius: 8px; padding: 0.55rem 0.85rem; font-size: 0.875rem; color: var(--color-text-primary); }
.lista__input:focus { outline: none; border-color: var(--color-gold); }
.lista__btn-limpiar { padding: 0.55rem 1rem; font-size: 0.8rem; align-self: flex-end; }

.lista__seccion-titulo {
  display: flex; align-items: center; gap: 0.75rem;
  font-family: var(--font-heading); font-size: 1rem; font-weight: 600;
  margin-bottom: 0.75rem; color: var(--color-text-primary);
}
.lista__seccion-titulo--inactivos { color: var(--color-text-secondary); }

.lista__count {
  font-size: 0.75rem; color: var(--color-text-secondary);
  background: var(--color-bg-card); padding: 0.2rem 0.65rem;
  border-radius: 9999px; border: 1px solid var(--color-border);
}
.lista__count--inactivo { background: rgba(239,68,68,0.08); color: #f87171; border-color: rgba(239,68,68,0.25); }

.lista__loading { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: var(--color-text-muted); }
.lista__spinner { width: 32px; height: 32px; border: 3px solid var(--color-border); border-top-color: var(--color-gold-400); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.lista__table-wrapper { padding: 0; overflow-x: auto; }
.lista__table-wrapper--inactivo { opacity: 0.85; }
.lista__table { width: 100%; border-collapse: collapse; }
.lista__table th { text-align: left; padding: 0.875rem 1.25rem; font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--color-border); background: var(--color-bg-secondary); }
.lista__table th:first-child { border-radius: var(--radius-xl) 0 0 0; }
.lista__table th:last-child { border-radius: 0 var(--radius-xl) 0 0; }
.lista__table td { padding: 0.875rem 1.25rem; font-size: 0.875rem; color: var(--color-text-primary); border-bottom: 1px solid var(--color-border); }
.lista__row { transition: background 0.2s; }
.lista__row:hover { background: var(--color-bg-hover); }
.lista__row:last-child td { border-bottom: none; }
.lista__row--inactivo td { color: var(--color-text-secondary); }

.lista__barbero-info { display: flex; align-items: center; gap: 0.75rem; }
.lista__avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-500)); color: var(--color-bg-primary); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; font-size: 0.8125rem; flex-shrink: 0; }
.lista__avatar--inactivo { background: linear-gradient(135deg, #9ca3af, #6b7280); }
.lista__antiguedad { color: var(--color-gold-400); font-weight: 600; }

.lista__actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.lista__btn { padding: 0.4rem 0.75rem; font-size: 0.8125rem; }
.lista__btn--danger { padding: 0.4rem 0.75rem; font-size: 0.8125rem; background: transparent; border: 1px solid #ef4444; color: #ef4444; border-radius: var(--radius-md); cursor: pointer; transition: background 0.2s; }
.lista__btn--danger:hover { background: #fef2f2; }
.lista__btn--reactivar { padding: 0.4rem 0.75rem; font-size: 0.8125rem; background: transparent; border: 1px solid #16a34a; color: #16a34a; border-radius: var(--radius-md); cursor: pointer; transition: background 0.2s; }
.lista__btn--reactivar:hover { background: #f0fdf4; }

.lista__empty { text-align: center; padding: 2rem; color: var(--color-text-muted); font-size: 0.875rem; }

.lista__exito { margin-bottom: 1rem; padding: 0.875rem 1.25rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-lg); color: #15803d; font-size: 0.875rem; }

.lista__modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; }
.lista__modal { background: var(--color-bg-card); border-radius: var(--radius-xl); padding: 2rem; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.lista__modal-title { font-family: var(--font-heading); font-size: 1.125rem; font-weight: 700; margin-bottom: 0.75rem; }
.lista__modal-texto { font-size: 0.875rem; color: var(--color-text-secondary); margin-bottom: 1.5rem; line-height: 1.6; }
.lista__modal-acciones { display: flex; gap: 0.75rem; justify-content: flex-end; }
.lista__btn--danger-full { padding: 0.5rem 1.25rem; font-size: 0.875rem; background: #ef4444; border: none; color: white; border-radius: var(--radius-md); cursor: pointer; }
.lista__btn--danger-full:hover { background: #dc2626; }
.lista__btn--danger-full:disabled { opacity: 0.5; cursor: not-allowed; }
.lista__btn--reactivar-full { padding: 0.5rem 1.25rem; font-size: 0.875rem; background: #16a34a; border: none; color: white; border-radius: var(--radius-md); cursor: pointer; }
.lista__btn--reactivar-full:hover { background: #15803d; }
.lista__btn--reactivar-full:disabled { opacity: 0.5; cursor: not-allowed; }
</style>