<template>
  <div class="verhorario animate-fade-in">

    <div class="verhorario__header">
      <div>
        <h1 class="verhorario__title">📅 Horario de <span class="gold-text">{{ nombreBarbero }}</span></h1>
        <p class="verhorario__subtitle">Configuración de horario semanal activo</p>
      </div>
      <div style="display:flex; gap:0.75rem;">
        <router-link :to="`/admin/barberos/${idBarbero}/horario/editar`" class="btn-primary">
          ✏️ Editar horario
        </router-link>
        <button @click="$router.back()" class="btn-secondary">← Volver</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="verhorario__loading">
      <div class="verhorario__spinner"></div>
      <p>Cargando horario...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="verhorario__alerta verhorario__alerta--error">⚠️ {{ error }}</div>

    <div v-if="!cargando && horarioActivo">

      <!-- Info semana -->
      <div class="glass-card verhorario__semana">
        <div class="verhorario__semana-item">
          <span class="verhorario__semana-ico">📆</span>
          <div>
            <strong>Semana {{ horarioActivo.semana }}</strong>
            <p>Año {{ horarioActivo.ano }}</p>
          </div>
        </div>
        <div class="verhorario__semana-item">
          <span class="verhorario__semana-ico">✅</span>
          <div>
            <strong>Días laborales</strong>
            <p>{{ diasLaborales }} día(s) activos</p>
          </div>
        </div>
        <div class="verhorario__semana-item">
          <span class="verhorario__semana-ico">🗓️</span>
          <div>
            <strong>Día de descanso</strong>
            <p>{{ diaDescanso || 'No asignado' }}</p>
          </div>
        </div>
      </div>

      <!-- Tabla de días -->
      <div class="glass-card verhorario__tabla-wrap">
        <div class="verhorario__tabla-header">
          <h2 class="verhorario__tabla-titulo">Distribución semanal</h2>
        </div>
        <table class="verhorario__tabla">
          <thead>
            <tr>
              <th>Día</th>
              <th>Hora entrada</th>
              <th>Hora salida</th>
              <th>Horas efectivas</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="dia in horarioActivo.dias"
              :key="dia.dia"
              class="verhorario__fila"
              :class="{ 'verhorario__fila--descanso': dia.dia_descanso }"
            >
              <td>
                <span class="verhorario__dia-nombre">{{ dia.dia }}</span>
              </td>
              <td>{{ dia.dia_descanso ? '—' : dia.hora_entrada }}</td>
              <td>{{ dia.dia_descanso ? '—' : dia.hora_salida }}</td>
              <td>
                <span v-if="!dia.dia_descanso" class="verhorario__horas-badge">
                  {{ calcularHoras(dia.hora_entrada, dia.hora_salida) }}h efectivas
                </span>
                <span v-else class="verhorario__muted">Descanso</span>
              </td>
              <td>
                <span v-if="dia.dia_descanso" class="verhorario__badge verhorario__badge--descanso">
                  🗓️ Descanso
                </span>
                <span v-else class="verhorario__badge verhorario__badge--activo">
                  ✅ Laboral
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Sin horario -->
    <div v-if="!cargando && !horarioActivo && !error" class="glass-card verhorario__vacio">
      <p>Este barbero no tiene horario configurado para la semana actual.</p>
      <router-link :to="`/admin/barberos/${idBarbero}/horario/editar`" class="btn-primary" style="margin-top:1rem;">
        + Crear horario
      </router-link>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const route      = useRoute()
const idBarbero  = route.params.id

const cargando      = ref(true)
const error         = ref('')
const horarioActivo = ref(null)
const nombreBarbero = ref('')

const diasLaborales = computed(() =>
  horarioActivo.value?.dias.filter(d => !d.dia_descanso).length ?? 0
)

const diaDescanso = computed(() =>
  horarioActivo.value?.dias.find(d => d.dia_descanso)?.dia ?? null
)

function calcularHoras(entrada, salida) {
  const [h1, m1] = entrada.split(':').map(Number)
  const [h2, m2] = salida.split(':').map(Number)
  const total = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60 - 1
  return total > 0 ? total.toFixed(1) : 0
}

function getWeekNumber(d) {
  const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
  const dayNum = date.getUTCDay() || 7
  date.setUTCDate(date.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1))
  return Math.ceil((((date - yearStart) / 86400000) + 1) / 7)
}

onMounted(async () => {
  try {
    const perfil = await barberoService.getById(idBarbero)
    nombreBarbero.value = perfil.barbero?.nombre_completo ?? ''

    const data = await barberoService.getHorarios(idBarbero)
    const semanaActual = getWeekNumber(new Date())
    const anoActual    = new Date().getFullYear()

    // Buscar primero el horario de la semana actual
    // Si no existe, tomar el más reciente
    const horarioSemana = data.horarios?.find(
      h => h.semana === semanaActual && h.ano === anoActual
    )
    horarioActivo.value = horarioSemana ?? data.horarios?.[0] ?? null

  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar el horario'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.verhorario { max-width: 900px; }

.verhorario__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.verhorario__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.verhorario__subtitle { font-size: 0.875rem; color: var(--color-text-muted); }

.verhorario__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.verhorario__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-azul-real);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.verhorario__alerta {
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}
.verhorario__alerta--error { background:#fef2f2; border:1px solid #fecaca; color:var(--color-rojo-vintage); }

.verhorario__semana {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.25rem;
}

.verhorario__semana-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.verhorario__semana-ico { font-size: 1.375rem; flex-shrink: 0; }

.verhorario__semana-item strong {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.125rem;
}

.verhorario__semana-item p { font-size: 0.8125rem; color: var(--color-text-secondary); }

.verhorario__tabla-wrap { padding: 0; overflow-x: auto; }

.verhorario__tabla-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.verhorario__tabla-titulo {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
}

.verhorario__tabla { width: 100%; border-collapse: collapse; }

.verhorario__tabla th {
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

.verhorario__tabla td {
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  border-bottom: 1px solid var(--color-border-light);
}

.verhorario__fila { transition: background 0.15s; }
.verhorario__fila:hover { background: var(--color-bg-hover); }
.verhorario__fila:last-child td { border-bottom: none; }
.verhorario__fila--descanso { background: rgba(232, 220, 196, 0.15); }

.verhorario__dia-nombre { font-weight: 600; }

.verhorario__horas-badge {
  display: inline-flex;
  padding: 0.2rem 0.6rem;
  background: #EEF2FF;
  color: #3730a3;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.verhorario__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.verhorario__badge--activo  { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.verhorario__badge--descanso { background:var(--color-oro-suave); color:var(--color-bronce); border:1px solid var(--color-bronce); }

.verhorario__muted { font-size:0.8125rem; color:var(--color-text-muted); font-style:italic; }

.verhorario__vacio {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
  font-size: 0.875rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>