<template>
  <div class="horarios animate-fade-in">

    <!-- Encabezado -->
    <div class="horarios__header">
      <div>
        <h1 class="horarios__title">Gestión de <span class="gold-text">Horarios Semanales</span></h1>
        <p class="horarios__subtitle">Asignación FIFO de descansos por antigüedad</p>
      </div>
    </div>

    <!-- Navegación de semanas -->
    <div class="glass-card horarios__nav">
      <button @click="cambiarSemana(-1)" class="btn-secondary horarios__nav-btn">← Anterior</button>
      <div class="horarios__nav-info">
        <span class="horarios__semana-label">Semana {{ semana }}</span>
        <span class="horarios__ano-label">{{ getRangoSemana(semana, ano) }} · {{ ano }}</span>
      </div>
      <button @click="cambiarSemana(1)" class="btn-secondary horarios__nav-btn">Siguiente →</button>
    </div>

    <!-- Loading -->
    <div v-if="cargando" class="horarios__loading">
      <div class="horarios__spinner"></div>
      <p>Cargando horarios...</p>
    </div>

    <!-- Alertas -->
    <div v-if="error"        class="horarios__alerta horarios__alerta--error">⚠️ {{ error }}</div>
    <div v-if="mensajeExito" class="horarios__alerta horarios__alerta--exito">✅ {{ mensajeExito }}</div>

    <div v-if="!cargando && datos">

      <!-- Panel informativo -->
      <div class="glass-card horarios__info">
        <div class="horarios__info-item">
          <span class="horarios__info-ico">🟢</span>
          <div>
            <strong>Lógica FIFO</strong>
            <p>Mayor antigüedad descansa primero — Lunes tiene prioridad</p>
          </div>
        </div>
        <div class="horarios__info-item">
          <span class="horarios__info-ico">📋</span>
          <div>
            <strong>Días de descanso</strong>
            <p>Se asignan de Lunes a Jueves según orden de antigüedad</p>
          </div>
        </div>
        <div class="horarios__info-item">
          <span class="horarios__info-ico">⏰</span>
          <div>
            <strong>Horario operativo</strong>
            <p>10:00 – 22:00 · Mínimo 8 horas efectivas por día laboral</p>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="glass-card horarios__tabla-wrap">
        <div class="horarios__tabla-header">
        <h2 class="horarios__tabla-titulo">📅 Barberos — Semana {{ semana }}</h2>
        <button
          @click="generarSemana"
          :disabled="generando"
          class="btn-primary"
        >
          {{ generando ? 'Actualizando...' : '🔄 Actualizar semana' }}
        </button>
      </div>

        <table class="horarios__tabla">
         <thead>
          <tr>
            <th>Orden FIFO</th>
            <th>Barbero</th>
            <th>Antigüedad</th>
            <th>Día de descanso</th>
            <th>Acciones</th>
          </tr>
        </thead>
          <tbody>
          <tr
            v-for="(barbero, index) in datos.barberos"
            :key="barbero.id_barbero"
            class="horarios__fila"
          >
            <td>
              <span class="horarios__orden">{{ index + 1 }}</span>
            </td>
            <td>
              <div class="horarios__barbero">
                <div class="horarios__avatar">{{ barbero.nombre_completo.charAt(0) }}</div>
                <span>{{ barbero.nombre_completo }}</span>
              </div>
            </td>
            <td>
              <span class="horarios__antiguedad">{{ barbero.antiguedad_dias }} días</span>
            </td>
            <td>
              <span v-if="barbero.dia_descanso_fifo" class="horarios__descanso-badge">
                🗓️ {{ barbero.dia_descanso_fifo }}
              </span>
              <span v-else class="horarios__muted">Sin descanso esta semana</span>
            </td>
            <td>
              <div class="horarios__acciones">
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}/horario`"
                  class="horarios__btn horarios__btn--ver"
                >📅 Ver</router-link>
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}/horario/editar`"
                  class="horarios__btn horarios__btn--editar"
                >✏️ Editar</router-link>
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}/almuerzos`"
                  class="horarios__btn horarios__btn--almuerzo"
                >🍽️ Almuerzos</router-link>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
      </div>


    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import barberoService from '../../services/barberoService.js'

const hoy    = new Date()
const semana = ref(getWeekNumber(hoy))
const ano    = ref(hoy.getFullYear())

const datos        = ref(null)
const cargando     = ref(true)
const generando    = ref(false)
const error        = ref('')
const mensajeExito = ref('')

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

async function cargar() {
  cargando.value = true
  error.value    = ''
  try {
    datos.value = await barberoService.getHorarioSemana(semana.value, ano.value)
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar los horarios'
  } finally {
    cargando.value = false
  }
}

async function generarSemana() {
  generando.value    = true
  error.value        = ''
  mensajeExito.value = ''
  try {
    await barberoService.generarHorarioSemana(semana.value, ano.value)
    mensajeExito.value = `Semana ${semana.value} generada correctamente`
    await cargar()
    setTimeout(() => { mensajeExito.value = '' }, 5000)
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al generar la semana'
  } finally {
    generando.value = false
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
.horarios { max-width: 1100px; }

.horarios__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.horarios__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.horarios__subtitle {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.horarios__nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  margin-bottom: 1.25rem;
}

.horarios__nav-btn { padding: 0.5rem 1rem; font-size: 0.875rem; }

.horarios__nav-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.125rem;
}

.horarios__semana-label {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.horarios__ano-label {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
}

.horarios__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}

.horarios__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-azul-real);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.horarios__alerta {
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}
.horarios__alerta--error { background:#fef2f2; border:1px solid #fecaca; color:var(--color-rojo-vintage); }
.horarios__alerta--exito { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }

.horarios__info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.25rem;
}

.horarios__info-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.horarios__info-ico { font-size: 1.375rem; flex-shrink: 0; margin-top: 0.125rem; }

.horarios__info-item strong {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 0.2rem;
}

.horarios__info-item p { font-size: 0.8125rem; color: var(--color-text-secondary); }

.horarios__tabla-wrap { padding: 0; overflow-x: auto; margin-bottom: 1.25rem; }

.horarios__tabla-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.horarios__tabla-titulo {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
}

.horarios__generada-badge {
  font-size: 0.875rem;
  font-weight: 600;
  color: #15803d;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
}

.horarios__tabla { width: 100%; border-collapse: collapse; }

.horarios__tabla th {
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

.horarios__tabla td {
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  border-bottom: 1px solid var(--color-border-light);
}

.horarios__fila { transition: background 0.15s; }
.horarios__fila:hover { background: var(--color-bg-hover); }
.horarios__fila:last-child td { border-bottom: none; }

.horarios__orden {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--color-azul-oscuro);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
}

.horarios__barbero {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.horarios__avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-bronce), var(--color-oro-suave));
  color: var(--color-azul-oscuro);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.horarios__antiguedad { color: var(--color-azul-real); font-weight: 600; font-size: 0.8125rem; }

.horarios__descanso-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  background: #EEF2FF;
  color: #3730a3;
  border: 1px solid #c7d2fe;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.horarios__muted { font-size: 0.8125rem; color: var(--color-text-muted); font-style: italic; }

.horarios__acciones {
  display: flex;
  gap: 0.4rem;
  flex-wrap: nowrap;
}

.horarios__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.3rem 0.55rem;
  font-size: 0.7rem;
  font-weight: 500;
  border-radius: var(--radius-md);
  text-decoration: none;
  border: 1px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}

.horarios__btn--ver    { background:#EEF2FF; color:#3730a3; border-color:#c7d2fe; }
.horarios__btn--ver:hover { background:#e0e7ff; }

.horarios__btn--editar { background:var(--color-oro-suave); color:var(--color-bronce); border-color:var(--color-bronce); }
.horarios__btn--editar:hover { background:#ddd5c0; }

.horarios__btn--almuerzo { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
.horarios__btn--almuerzo:hover { background:#dcfce7; }

.horarios__leyenda { padding: 1.25rem 1.5rem; }

.horarios__leyenda-titulo {
  font-family: var(--font-heading);
  font-size: 0.9375rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.horarios__leyenda-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 0.75rem;
}

.horarios__leyenda-item {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.horarios__leyenda-ico { flex-shrink: 0; font-size: 1rem; margin-top: 0.1rem; }
</style>