<template>
  <div class="verhorario animate-fade-in">

    <div class="verhorario__header">
      <div>
        <h1 class="verhorario__title">📅 Horario de <span class="gold-text">{{ nombreBarbero }}</span></h1>
        <p class="verhorario__subtitle">Historial de asignaciones semanales</p>
      </div>
      <button @click="$router.back()" class="btn-secondary">← Volver</button>
    </div>

    <div v-if="cargando" class="verhorario__loading">
      <div class="verhorario__spinner"></div>
      <p>Cargando horario...</p>
    </div>

    <div v-if="error" class="verhorario__alerta verhorario__alerta--error">⚠️ {{ error }}</div>

    <div v-if="!cargando && semanaMasReciente">

      <div class="glass-card verhorario__semana">
        <div class="verhorario__semana-item">
          <span class="verhorario__semana-ico">📆</span>
          <div>
            <strong>Semana vigente</strong>
            <p>{{ semanaMasReciente.fecha_inicio }} – {{ semanaMasReciente.fecha_fin }}</p>
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
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="dia in semanaMasReciente.dias"
              :key="dia.dia"
              class="verhorario__fila"
              :class="{ 'verhorario__fila--descanso': dia.dia_descanso }"
            >
              <td><span class="verhorario__dia-nombre">{{ dia.dia }}</span></td>
              <td>{{ dia.dia_descanso ? '—' : dia.hora_entrada?.substring(0, 5) }}</td>
              <td>{{ dia.dia_descanso ? '—' : dia.hora_salida?.substring(0, 5) }}</td>
              <td>
                <span v-if="dia.dia_descanso" class="verhorario__badge verhorario__badge--descanso">🗓️ Descanso</span>
                <span v-else class="verhorario__badge verhorario__badge--activo">✅ Laboral</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Historial de semanas anteriores -->
      <div v-if="historialAnterior.length" class="glass-card verhorario__historial">
        <h3 class="verhorario__tabla-titulo" style="margin-bottom: 0.75rem;">Semanas anteriores</h3>
        <div v-for="(s, i) in historialAnterior" :key="i" class="verhorario__historial-fila">
          <span>{{ s.fecha_inicio }} – {{ s.fecha_fin }}</span>
          <span class="verhorario__descanso-mini">
            Descanso: {{ s.dias.find(d => d.dia_descanso)?.dia || 'N/A' }}
          </span>
        </div>
      </div>

    </div>

    <div v-if="!cargando && !semanaMasReciente && !error" class="glass-card verhorario__vacio">
      <p>Este barbero aún no tiene un horario semanal generado.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const route      = useRoute()
const idBarbero  = route.params.id

const cargando       = ref(true)
const error          = ref('')
const semanas         = ref([])
const nombreBarbero  = ref('')

const semanaMasReciente = computed(() => semanas.value[0] ?? null)
const historialAnterior = computed(() => semanas.value.slice(1))

const diasLaborales = computed(() =>
  semanaMasReciente.value?.dias.filter(d => !d.dia_descanso).length ?? 0
)

const diaDescanso = computed(() =>
  semanaMasReciente.value?.dias.find(d => d.dia_descanso)?.dia ?? null
)

onMounted(async () => {
  try {
    const perfil = await barberoService.getById(idBarbero)
    nombreBarbero.value = perfil.barbero?.nombre_completo ?? ''

    const data = await barberoService.getHorarios(idBarbero)
    semanas.value = data.horarios ?? []
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar el horario'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
/* ── idéntico a tu versión previa, solo agrego: ── */
.verhorario__historial { padding: 1.25rem 1.5rem; }
.verhorario__historial-fila { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.8125rem; border-bottom: 1px solid var(--color-border-light); }
.verhorario__historial-fila:last-child { border-bottom: none; }
.verhorario__descanso-mini { color: var(--color-text-muted); }

/* ── resto del style igual al que ya tenías (sin .verhorario__horas-badge, ya no aplica) ── */
.verhorario { width: 100%;
  max-width: 1200px; /* Alineado con el resto de tus vistas */
  margin: 0;         /* Elimina el centrado automático */
  padding: 0 2rem;   /* Espaciado lateral consistente */
}
.verhorario__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; gap: 1rem; }
.verhorario__title { font-family: var(--font-heading); font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
.verhorario__subtitle { font-size: 0.875rem; color: var(--color-text-muted); }
.verhorario__loading { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: var(--color-text-muted); }
.verhorario__spinner { width: 32px; height: 32px; border: 3px solid var(--color-border); border-top-color: var(--color-azul-real); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.verhorario__alerta { padding: 0.875rem 1.25rem; border-radius: var(--radius-lg); font-size: 0.875rem; margin-bottom: 1.25rem; }
.verhorario__alerta--error { background:#fef2f2; border:1px solid #fecaca; color:var(--color-rojo-vintage); }
.verhorario__semana { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
.verhorario__semana-item { display: flex; align-items: flex-start; gap: 0.75rem; }
.verhorario__semana-ico { font-size: 1.375rem; flex-shrink: 0; }
.verhorario__semana-item strong { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.125rem; }
.verhorario__semana-item p { font-size: 0.8125rem; color: var(--color-text-secondary); }
.verhorario__tabla-wrap { padding: 0; overflow-x: auto; }
.verhorario__tabla-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.verhorario__tabla-titulo { font-family: var(--font-heading); font-size: 1rem; font-weight: 600; }
.verhorario__tabla { width: 100%; border-collapse: collapse; }
.verhorario__tabla th { text-align: left; padding: 0.75rem 1.25rem; font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; background: var(--color-bg-secondary); border-bottom: 1px solid var(--color-border); }
.verhorario__tabla td { padding: 0.875rem 1.25rem; font-size: 0.875rem; border-bottom: 1px solid var(--color-border-light); }
.verhorario__fila { transition: background 0.15s; }
.verhorario__fila:hover { background: var(--color-bg-hover); }
.verhorario__fila:last-child td { border-bottom: none; }
.verhorario__fila--descanso { background: rgba(232, 220, 196, 0.15); }
.verhorario__dia-nombre { font-weight: 600; }
.verhorario__badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.verhorario__badge--activo { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.verhorario__badge--descanso { background:var(--color-oro-suave); color:var(--color-bronce); border:1px solid var(--color-bronce); }
.verhorario__vacio { text-align: center; padding: 3rem; color: var(--color-text-muted); font-size: 0.875rem; display: flex; flex-direction: column; align-items: center; }
</style>
