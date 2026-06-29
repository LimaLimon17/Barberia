<template>
  <div class="almuerzos animate-fade-in">

    <div class="almuerzos__header">
      <div>
        <h1 class="almuerzos__title">
          🍽️ Registro de <span class="gold-text">Almuerzos</span>
        </h1>
        <p class="almuerzos__subtitle" v-if="nombreBarbero">{{ nombreBarbero }}</p>
      </div>
      <button @click="$router.back()" class="btn-secondary">← Volver</button>
    </div>

    <!-- Selector de semana -->
    <div class="glass-card almuerzos__semana">
      <button @click="cambiarSemana(-1)" class="btn-secondary almuerzos__nav-btn">← Anterior</button>
      <div class="almuerzos__semana-info">
        <span class="almuerzos__semana-label">Semana {{ semana }}</span>
        <span class="almuerzos__semana-rango">{{ rangoSemana }}</span>
      </div>
      <button @click="cambiarSemana(1)" class="btn-secondary almuerzos__nav-btn">Siguiente →</button>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="almuerzos__loading">
      <div class="almuerzos__spinner"></div>
      <p>Cargando registros...</p>
    </div>

    <!-- Alertas -->
    <div v-if="error"        class="almuerzos__alerta almuerzos__alerta--error">⚠️ {{ error }}</div>
    <div v-if="mensajeExito" class="almuerzos__alerta almuerzos__alerta--exito">✅ {{ mensajeExito }}</div>

    <div v-if="!cargando">

      <!-- Tabla de registros -->
      <div class="glass-card almuerzos__tabla-wrap">
        <div class="almuerzos__tabla-header">
          <h2 class="almuerzos__tabla-titulo">
            {{ registros.length ? `${registros.length} registro(s) esta semana` : 'Sin registros esta semana' }}
          </h2>
        </div>

        <table v-if="registros.length" class="almuerzos__tabla">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Hora salida</th>
              <th>Hora retorno</th>
              <th>Duración</th>
              <th>Observación</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registros" :key="r.id_registro" class="almuerzos__fila">
              <td>{{ formatearFecha(r.fecha) }}</td>
              <td>
                <span class="almuerzos__hora almuerzos__hora--salida">
                  🚶 {{ r.hora_salida || '—' }}
                </span>
              </td>
              <td>
                <span class="almuerzos__hora almuerzos__hora--retorno">
                  🔙 {{ r.hora_retorno || '—' }}
                </span>
              </td>
              <td>
                <span v-if="r.hora_salida && r.hora_retorno" class="almuerzos__duracion">
                  {{ calcularDuracion(r.hora_salida, r.hora_retorno) }}
                </span>
                <span v-else class="almuerzos__muted">En curso</span>
              </td>
              <td>
                <span class="almuerzos__muted">{{ r.observacion || '—' }}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="almuerzos__vacio">
          <p>No hay registros de almuerzo para esta semana.</p>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const route         = useRoute()
const idBarbero     = route.params.id
const cargando      = ref(true)
const error         = ref('')
const mensajeExito  = ref('')
const registros     = ref([])
const nombreBarbero = ref('')

const hoy    = new Date()
const semana = ref(getWeekNumber(hoy))
const ano    = ref(hoy.getFullYear())

const rangoSemana = computed(() => getRangoSemana(semana.value, ano.value))

function getWeekNumber(d) {
  const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
  const dayNum = date.getUTCDay() || 7
  date.setUTCDate(date.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1))
  return Math.ceil((((date - yearStart) / 86400000) + 1) / 7)
}

function getRangoSemana(sem, yr) {
  const simple = new Date(yr, 0, 1 + (sem - 1) * 7)
  const dow    = simple.getDay()
  const lunes  = new Date(simple)
  lunes.setDate(simple.getDate() - (dow <= 4 ? dow - 1 : dow - 8))
  const domingo = new Date(lunes)
  domingo.setDate(lunes.getDate() + 6)
  const opts = { day: 'numeric', month: 'short' }
  return `${lunes.toLocaleDateString('es-BO', opts)} – ${domingo.toLocaleDateString('es-BO', opts)}`
}

function getLunesDomingo(sem, yr) {
  const simple = new Date(yr, 0, 1 + (sem - 1) * 7)
  const dow    = simple.getDay()
  const lunes  = new Date(simple)
  lunes.setDate(simple.getDate() - (dow <= 4 ? dow - 1 : dow - 8))
  const domingo = new Date(lunes)
  domingo.setDate(lunes.getDate() + 6)
  return {
    inicio: lunes.toISOString().split('T')[0],
    fin:    domingo.toISOString().split('T')[0],
  }
}

function formatearFecha(fecha) {
  return new Date(fecha + 'T00:00:00').toLocaleDateString('es-BO', {
    weekday: 'short', day: 'numeric', month: 'short'
  })
}

function calcularDuracion(salida, retorno) {
  const [h1, m1] = salida.split(':').map(Number)
  const [h2, m2] = retorno.split(':').map(Number)
  const mins = (h2 * 60 + m2) - (h1 * 60 + m1)
  if (mins <= 0) return '—'
  return `${mins} min`
}

async function cargar() {
  cargando.value = true
  error.value    = ''
  try {
    const { inicio, fin } = getLunesDomingo(semana.value, ano.value)
    const data = await barberoService.getAlmuerzos(idBarbero, inicio, fin)
    registros.value     = data.registros
    if (data.registros.length > 0) {
      nombreBarbero.value = data.registros[0].nombre_barbero
    } else {
      // Cargar nombre del barbero desde perfil si no hay registros
      const perfil = await barberoService.getById(idBarbero)
      nombreBarbero.value = perfil.barbero?.nombre_completo || ''
    }
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar los registros'
  } finally {
    cargando.value = false
  }
}

function cambiarSemana(delta) {
  semana.value += delta
  if (semana.value < 1)  { semana.value = 52; ano.value-- }
  if (semana.value > 52) { semana.value = 1;  ano.value++ }
  cargar()
}

onMounted(cargar)
</script>

<style scoped>
.almuerzos { max-width: 900px; }

.almuerzos__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.almuerzos__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.almuerzos__subtitle {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.almuerzos__semana {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  margin-bottom: 1.25rem;
}

.almuerzos__nav-btn { padding: 0.5rem 1rem; font-size: 0.875rem; }

.almuerzos__semana-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.125rem;
}

.almuerzos__semana-label {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 700;
}

.almuerzos__semana-rango {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.almuerzos__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.almuerzos__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-azul-real);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.almuerzos__alerta {
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}
.almuerzos__alerta--error { background:#fef2f2; border:1px solid #fecaca; color:var(--color-rojo-vintage); }
.almuerzos__alerta--exito { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }

.almuerzos__tabla-wrap { padding: 0; overflow-x: auto; }

.almuerzos__tabla-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.almuerzos__tabla-titulo {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
}

.almuerzos__tabla {
  width: 100%;
  border-collapse: collapse;
}

.almuerzos__tabla th {
  text-align: left;
  padding: 0.75rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
}

.almuerzos__tabla td {
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  border-bottom: 1px solid var(--color-border-light);
}

.almuerzos__fila { transition: background 0.15s; }
.almuerzos__fila:hover { background: var(--color-bg-hover); }
.almuerzos__fila:last-child td { border-bottom: none; }

.almuerzos__hora {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.almuerzos__hora--salida  { color: var(--color-rojo-vintage); }
.almuerzos__hora--retorno { color: #15803d; }

.almuerzos__duracion {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-azul-real);
  background: #EEF2FF;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.almuerzos__muted {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  font-style: italic;
}

.almuerzos__vacio {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
  font-size: 0.875rem;
}
</style>