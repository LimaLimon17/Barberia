<template>
  <div class="edithorario animate-fade-in">

    <div class="edithorario__header">
      <div>
        <h1 class="edithorario__title">✏️ Editar horario de <span class="gold-text">{{ nombreBarbero }}</span></h1>
        <p class="edithorario__subtitle">Modifica los días y horarios del barbero</p>
      </div>
      <button @click="$router.back()" class="btn-secondary">← Volver</button>
    </div>

    <div v-if="error"        class="edithorario__alerta edithorario__alerta--error">⚠️ {{ error }}</div>
    <div v-if="mensajeExito" class="edithorario__alerta edithorario__alerta--exito">✅ {{ mensajeExito }}</div>

    <div v-if="cargando" class="edithorario__loading">
      <div class="edithorario__spinner"></div>
      <p>Cargando horario...</p>
    </div>

    <form v-if="!cargando" @submit.prevent="guardar" class="edithorario__form">

      <div class="glass-card edithorario__seccion">
        <h2 class="edithorario__seccion-titulo">🗓️ Días y horarios</h2>
        <p class="edithorario__seccion-desc">
          Cada día laboral activo debe tener mínimo <strong>8 horas efectivas</strong>
          (se descuenta 1h de almuerzo). Horario operativo: 10:00 – 22:00.
        </p>

        <p v-if="errores.dias" class="edithorario__error" style="margin-bottom:1rem;">
          {{ errores.dias }}
        </p>

        <div class="edithorario__dias">
          <div
            v-for="dia in dias"
            :key="dia.key"
            class="edithorario__dia"
            :class="{
              'edithorario__dia--activo':   dia.activo,
              'edithorario__dia--inactivo': !dia.activo,
            }"
          >
            <div class="edithorario__dia-header">
              <label class="edithorario__dia-toggle">
                <input type="checkbox" v-model="dia.activo" />
                <span class="edithorario__dia-nombre">{{ dia.nombre }}</span>
              </label>
              <span
                v-if="dia.activo && !dia.dia_descanso"
                class="edithorario__dia-horas"
                :class="horasValidas(dia) ? 'edithorario__dia-horas--ok' : 'edithorario__dia-horas--mal'"
              >
                {{ calcularHoras(dia) }}
              </span>
              <span v-if="dia.activo && dia.dia_descanso" class="edithorario__dia-badge">Descanso</span>
            </div>

            <div v-if="dia.activo" class="edithorario__dia-body">
              <div class="edithorario__dia-inputs">
                <div class="edithorario__campo-pequeño">
                  <label class="label">Entrada</label>
                  <input v-model="dia.hora_entrada" type="time" min="10:00" max="22:00"
                    class="input-field" :disabled="dia.dia_descanso" />
                </div>
                <span class="edithorario__separador">→</span>
                <div class="edithorario__campo-pequeño">
                  <label class="label">Salida</label>
                  <input v-model="dia.hora_salida" type="time" min="10:00" max="22:00"
                    class="input-field" :disabled="dia.dia_descanso" />
                </div>
              </div>
              <label class="edithorario__descanso-toggle">
                <input type="checkbox" v-model="dia.dia_descanso" />
                <span>Marcar como día de descanso</span>
              </label>
            </div>

            <div v-else class="edithorario__dia-inactivo-msg">No trabaja este día</div>
          </div>
        </div>
      </div>

      <div class="edithorario__footer">
        <button type="button" @click="$router.back()" class="btn-secondary">Cancelar</button>
        <button type="submit" :disabled="guardando" class="btn-primary">
          {{ guardando ? 'Guardando...' : '💾 Guardar cambios' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const route     = useRoute()
const router    = useRouter()
const idBarbero = route.params.id

const cargando      = ref(true)
const guardando     = ref(false)
const error         = ref('')
const mensajeExito  = ref('')
const errores       = ref({})
const nombreBarbero = ref('')

const diasBase = [
  { key: 'Lunes',     nombre: 'Lunes'     },
  { key: 'Martes',    nombre: 'Martes'    },
  { key: 'Miércoles', nombre: 'Miércoles' },
  { key: 'Jueves',    nombre: 'Jueves'    },
  { key: 'Viernes',   nombre: 'Viernes'   },
  { key: 'Sábado',    nombre: 'Sábado'    },
  { key: 'Domingo',   nombre: 'Domingo'   },
]

const dias = ref(diasBase.map(d => ({
  ...d,
  activo:       false,
  hora_entrada: '10:00',
  hora_salida:  '19:00',
  dia_descanso: false,
})))

function calcularHoras(dia) {
  if (!dia.hora_entrada || !dia.hora_salida) return ''
  const [h1, m1] = dia.hora_entrada.split(':').map(Number)
  const [h2, m2] = dia.hora_salida.split(':').map(Number)
  const total = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60 - 1
  return total > 0 ? `${total.toFixed(1)}h efectivas` : '0h efectivas'
}

function horasValidas(dia) {
  const [h1, m1] = dia.hora_entrada.split(':').map(Number)
  const [h2, m2] = dia.hora_salida.split(':').map(Number)
  return ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60 - 1 >= 8
}

function validar() {
  const e = {}
  const activos = dias.value.filter(d => d.activo)
  if (activos.length === 0) { e.dias = 'Debe activar al menos un día'; errores.value = e; return false }
  for (const d of activos) {
    if (d.dia_descanso) continue
    if (d.hora_entrada < '10:00') { e.dias = `${d.nombre}: entrada no puede ser antes de las 10:00`; break }
    if (d.hora_salida  > '22:00') { e.dias = `${d.nombre}: salida no puede ser después de las 22:00`; break }
    if (!horasValidas(d))         { e.dias = `${d.nombre}: mínimo 8 horas efectivas`; break }
  }
  errores.value = e
  return Object.keys(e).length === 0
}

async function guardar() {
  if (!validar()) return
  guardando.value = true
  error.value     = ''

  const diasActivos = dias.value
    .filter(d => d.activo)
    .map(d => ({
      dia:          d.key,
      hora_entrada: d.hora_entrada,
      hora_salida:  d.hora_salida,
      dia_descanso: d.dia_descanso,
    }))

  const semana = new Date()
  const ano    = semana.getFullYear()

  try {
    await barberoService.crearHorario({
      id_barbero: idBarbero,
      semana:     getWeekNumber(semana),
      ano,
      dias:       diasActivos,
    })
    mensajeExito.value = 'Horario guardado correctamente'
    setTimeout(() => router.push(`/admin/barberos/${idBarbero}/horario`), 1500)
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al guardar el horario'
  } finally {
    guardando.value = false
  }
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

    // Cargar horario existente si hay uno
    const data = await barberoService.getHorarios(idBarbero)
    const horario = data.horarios?.[0]
    if (horario) {
      dias.value = diasBase.map(base => {
        const diaExistente = horario.dias.find(d => d.dia === base.key)
        if (diaExistente) {
          return {
            ...base,
            activo:       true,
            hora_entrada: diaExistente.hora_entrada?.substring(0, 5) ?? '10:00',
            hora_salida:  diaExistente.hora_salida?.substring(0, 5)  ?? '19:00',
            dia_descanso: !!diaExistente.dia_descanso,
          }
        }
        return { ...base, activo: false, hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false }
      })
    }
  } catch (err) {
    error.value = 'Error al cargar el horario'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.edithorario { max-width: 900px; }

.edithorario__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.edithorario__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.edithorario__subtitle { font-size: 0.875rem; color: var(--color-text-muted); }

.edithorario__loading {
  display: flex; flex-direction: column; align-items: center;
  gap: 1rem; padding: 3rem; color: var(--color-text-muted);
}

.edithorario__spinner {
  width: 32px; height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-azul-real);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.edithorario__alerta {
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}
.edithorario__alerta--error { background:#fef2f2; border:1px solid #fecaca; color:var(--color-rojo-vintage); }
.edithorario__alerta--exito { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }

.edithorario__form { display: flex; flex-direction: column; gap: 1.5rem; }

.edithorario__seccion { padding: 1.75rem; }

.edithorario__seccion-titulo {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.375rem;
}

.edithorario__seccion-desc {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  margin-bottom: 1.5rem;
}

.edithorario__error { font-size: 0.75rem; color: var(--color-rojo-vintage); }

.edithorario__dias {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.edithorario__dia {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all 0.2s;
}

.edithorario__dia--activo  { border-color: var(--color-azul-real); box-shadow: 0 2px 8px rgba(22,62,113,0.08); }
.edithorario__dia--inactivo { opacity: 0.6; }

.edithorario__dia-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border-light);
}

.edithorario__dia-toggle {
  display: flex; align-items: center; gap: 0.5rem; cursor: pointer;
}

.edithorario__dia-toggle input[type="checkbox"] {
  width: 16px; height: 16px; accent-color: var(--color-azul-real); cursor: pointer;
}

.edithorario__dia-nombre {
  font-family: var(--font-heading);
  font-size: 0.9375rem;
  font-weight: 600;
}

.edithorario__dia-horas {
  font-size: 0.75rem; font-weight: 600;
  padding: 0.2rem 0.6rem; border-radius: 9999px;
}

.edithorario__dia-horas--ok  { background:#dcfce7; color:#166534; }
.edithorario__dia-horas--mal { background:#fee2e2; color:var(--color-rojo-vintage); }

.edithorario__dia-badge {
  font-size: 0.75rem; font-weight: 600;
  padding: 0.2rem 0.6rem; border-radius: 9999px;
  background: var(--color-oro-suave); color: var(--color-bronce);
}

.edithorario__dia-body {
  padding: 1rem;
  background: var(--color-bg-primary);
  display: flex; flex-direction: column; gap: 0.875rem;
}

.edithorario__dia-inputs {
  display: flex; align-items: flex-end; gap: 0.75rem;
}

.edithorario__campo-pequeño { flex: 1; display: flex; flex-direction: column; gap: 0.25rem; }

.edithorario__separador {
  font-size: 1rem; color: var(--color-bronce); padding-bottom: 0.625rem;
}

.edithorario__descanso-toggle {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.8125rem; color: var(--color-text-muted); cursor: pointer;
}

.edithorario__descanso-toggle input[type="checkbox"] {
  accent-color: var(--color-azul-real); cursor: pointer;
}

.edithorario__dia-inactivo-msg {
  padding: 0.75rem 1rem;
  font-size: 0.8125rem; color: var(--color-text-muted);
  background: var(--color-bg-primary); font-style: italic;
}

.edithorario__footer {
  display: flex; justify-content: flex-end; gap: 1rem; padding-top: 0.5rem;
}

@media (max-width: 640px) {
  .edithorario__dias { grid-template-columns: 1fr; }
}
</style>
