<template>
  <div class="registrar animate-fade-in">

    <div class="registrar__header">
      <div>
        <h1 class="registrar__title">Registrar <span class="gold-text">nuevo barbero</span></h1>
        <p class="registrar__subtitle">Complete los datos y asigne el horario inicial obligatorio</p>
      </div>
      <button @click="$router.push('/admin/barberos')" class="btn-secondary">
        ← Volver
      </button>
    </div>

    <div v-if="errorGeneral" class="registrar__alerta registrar__alerta--error">
      ⚠️ {{ errorGeneral }}
    </div>

    <div v-if="exitoso" class="registrar__alerta registrar__alerta--exito">
      ✅ Barbero registrado correctamente. Redirigiendo...
    </div>

    <form @submit.prevent="registrar" class="registrar__form">

      <div class="glass-card registrar__seccion">
        <h2 class="registrar__seccion-titulo">👤 Datos personales</h2>

        <div class="registrar__grid">

          <div class="registrar__campo">
            <label class="label">Primer nombre *</label>
            <input
              v-model="form.nombre1"
              type="text"
              placeholder="Ej: Carlos"
              class="input-field"
              :class="{ 'input-field--error': errores.nombre1 }"
            />
            <span v-if="errores.nombre1" class="registrar__error">{{ errores.nombre1 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Segundo nombre</label>
            <input
              v-model="form.nombre2"
              type="text"
              placeholder="Ej: Andres"
              class="input-field"
              :class="{ 'input-field--error': errores.nombre2 }"
            />
            <span v-if="errores.nombre2" class="registrar__error">{{ errores.nombre2 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Primer apellido *</label>
            <input
              v-model="form.apellido1"
              type="text"
              placeholder="Ej: Mamani"
              class="input-field"
              :class="{ 'input-field--error': errores.apellido1 }"
            />
            <span v-if="errores.apellido1" class="registrar__error">{{ errores.apellido1 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Segundo apellido</label>
            <input
              v-model="form.apellido2"
              type="text"
              placeholder="Ej: Quispe"
              class="input-field"
              :class="{ 'input-field--error': errores.apellido2 }"
            />
            <span v-if="errores.apellido2" class="registrar__error">{{ errores.apellido2 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Correo electrónico *</label>
            <input
              v-model="form.correo"
              type="email"
              placeholder="correo@ejemplo.com"
              class="input-field"
              :class="{ 'input-field--error': errores.correo }"
            />
            <span v-if="errores.correo" class="registrar__error">{{ errores.correo }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Contraseña *</label>
            <div class="registrar__input-wrapper">
              <input
                v-model="form.contrasena"
                :type="verContrasena ? 'text' : 'password'"
                placeholder="Mínimo 6 caracteres"
                class="input-field"
                :class="{ 'input-field--error': errores.contrasena }"
              />
              <button
                type="button"
                @mousedown="verContrasena = true"
                @mouseup="verContrasena = false"
                @mouseleave="verContrasena = false"
                class="registrar__ojo"
                tabindex="-1"
              >
                {{ verContrasena ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.contrasena" class="registrar__error">{{ errores.contrasena }}</span>
          </div>
          
          <div class="registrar__campo">
            <label class="label">Fecha de ingreso *</label>
            <input
              v-model="form.fecha_ingreso"
              type="date"
              :max="hoy"
              class="input-field"
              :class="{ 'input-field--error': errores.fecha_ingreso }"
            />
            <span v-if="errores.fecha_ingreso" class="registrar__error">{{ errores.fecha_ingreso }}</span>
          </div>
        </div>
      </div>

      <div class="glass-card registrar__seccion">
        <div class="registrar__seccion-header">
          <h2 class="registrar__seccion-titulo">🗓️ Horario inicial</h2>
          <p class="registrar__seccion-desc">
            Cada día laboral activo debe tener mínimo <strong>8 horas efectivas</strong> (se descuenta 1h de almuerzo automáticamente).
          </p>
        </div>

        <span v-if="errores.dias" class="registrar__error" style="display:block; margin-bottom: 1rem;">
          {{ errores.dias }}
        </span>

        <div class="registrar__dias">
          <div
            v-for="dia in diasSemana"
            :key="dia.key"
            class="registrar__dia"
            :class="{
              'registrar__dia--activo': dia.activo,
              'registrar__dia--inactivo': !dia.activo
            }"
          >
            <div class="registrar__dia-header">
              <label class="registrar__dia-toggle">
                <input type="checkbox" v-model="dia.activo" />
                <span class="registrar__dia-nombre">{{ dia.nombre }}</span>
              </label>
              <span
                v-if="dia.activo && !dia.dia_descanso"
                class="registrar__dia-horas"
                :class="horasValidas(dia) ? 'registrar__dia-horas--ok' : 'registrar__dia-horas--mal'"
              >
                {{ calcularHoras(dia) }}
              </span>
              <span v-if="dia.activo && dia.dia_descanso" class="registrar__dia-badge">
                Descanso
              </span>
            </div>

            <div v-if="dia.activo" class="registrar__dia-body">
              <div class="registrar__dia-horas-inputs">
                <div class="registrar__campo-pequeño">
                  <label class="label">Entrada</label>
                  <input
                    v-model="dia.hora_entrada"
                    type="time"
                    class="input-field"
                    :disabled="dia.dia_descanso"
                  />
                </div>
                <span class="registrar__dia-separador">→</span>
                <div class="registrar__campo-pequeño">
                  <label class="label">Salida</label>
                  <input
                    v-model="dia.hora_salida"
                    type="time"
                    class="input-field"
                    :disabled="dia.dia_descanso"
                  />
                </div>
              </div>

            <label
              v-if="DIAS_DESCANSO_PERMITIDOS.includes(dia.key)"
              class="registrar__descanso-toggle"
            >
              <input type="checkbox" v-model="dia.dia_descanso" />
              <span>Marcar como día de descanso</span>
            </label>
            </div>

            <div v-else class="registrar__dia-inactivo-msg">
              No trabaja este día
            </div>
          </div>
        </div>
      </div>

      <div class="registrar__footer">
        <button
          type="button"
          @click="$router.push('/admin/barberos')"
          class="btn-secondary"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="cargando"
          class="btn-primary"
        >
          {{ cargando ? 'Registrando...' : '✅ Registrar barbero' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const router       = useRouter()
const cargando     = ref(false)
const errorGeneral = ref('')
const exitoso      = ref(false)
const errores      = ref({})
const hoy = new Date().toISOString().split('T')[0]
const verContrasena = ref(false)
const DIAS_DESCANSO_PERMITIDOS = ['Lunes', 'Martes', 'Miércoles', 'Jueves']

const form = ref({
  nombre1:       '',
  nombre2:       '',
  apellido1:     '',
  apellido2:     '',
  correo:        '',
  contrasena:    '',
  fecha_ingreso: hoy,
})

const diasSemana = ref([
  { key: 'Lunes',     nombre: 'Lunes',     activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Martes',    nombre: 'Martes',    activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Miércoles', nombre: 'Miércoles', activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Jueves',    nombre: 'Jueves',    activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Viernes',   nombre: 'Viernes',   activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Sábado',    nombre: 'Sábado',    activo: true,  hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Domingo', nombre: 'Domingo', activo: true, hora_entrada: '10:00', hora_salida: '19:00', dia_descanso: false },
])

function calcularHoras(dia) {
  if (!dia.hora_entrada || !dia.hora_salida) return ''
  const [h1, m1] = dia.hora_entrada.split(':').map(Number)
  const [h2, m2] = dia.hora_salida.split(':').map(Number)
  const total = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60 - 1
  if (total <= 0) return '0h efectivas'
  return `${total.toFixed(1)}h efectivas`
}

function horasValidas(dia) {
  if (dia.dia_descanso) return true
  const [h1, m1] = dia.hora_entrada.split(':').map(Number)
  const [h2, m2] = dia.hora_salida.split(':').map(Number)
  const total = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60 - 1
  return total >= 8
}

function validar() {
  const e = {}
  const soloLetras = /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/

  // Validación de Nombres y Apellidos (Rechaza números)
  if (!form.value.nombre1.trim()) {
    e.nombre1 = 'El primer nombre es obligatorio'
  } else if (!soloLetras.test(form.value.nombre1.trim())) {
    e.nombre1 = 'El nombre solo debe contener letras'
  }

  if (form.value.nombre2.trim() && !soloLetras.test(form.value.nombre2.trim())) {
    e.nombre2 = 'El segundo nombre solo debe contener letras'
  }

  if (!form.value.apellido1.trim()) {
    e.apellido1 = 'El primer apellido es obligatorio'
  } else if (!soloLetras.test(form.value.apellido1.trim())) {
    e.apellido1 = 'El apellido solo debe contener letras'
  }

  if (form.value.apellido2.trim() && !soloLetras.test(form.value.apellido2.trim())) {
    e.apellido2 = 'El segundo apellido solo debe contener letras'
  }

  if (!form.value.correo.trim())         e.correo        = 'El correo es obligatorio'
  if (!form.value.contrasena.trim())     e.contrasena    = 'La contraseña es obligatoria'
  if (form.value.contrasena.length < 6)  e.contrasena    = 'Mínimo 6 caracteres'
  if (!form.value.fecha_ingreso)         e.fecha_ingreso = 'La fecha de ingreso es obligatoria'

  const diasActivos = diasSemana.value.filter(d => d.activo)
  if (diasActivos.length === 0) {
    e.dias = 'Debe activar al menos un día de trabajo'
  } else {
      for (const d of diasActivos) {
        // Solo Lunes-Jueves puede ser día de descanso
        if (d.dia_descanso && !DIAS_DESCANSO_PERMITIDOS.includes(d.key)) {
          e.dias = `El día ${d.nombre} no puede marcarse como descanso. Solo se permite de Lunes a Jueves`
          break
        }

        if (d.dia_descanso) continue

      // Validar coherencia de tiempo (Salida antes de entrada)
      if (d.hora_entrada >= d.hora_salida) {
        e.dias = `El día ${d.nombre}: la salida debe ser posterior a la entrada`
        break
      }

      // Validar rango operativo 10:00 – 22:00
      if (d.hora_entrada < '10:00') {
        e.dias = `El día ${d.nombre}: la entrada no puede ser antes de las 10:00`
        break
      }
      if (d.hora_salida > '22:00') {
        e.dias = `El día ${d.nombre}: la salida no puede ser después de las 22:00`
        break
      }

      // Validar mínimo 8h efectivas
      if (!horasValidas(d)) {
        e.dias = `El día ${d.nombre} no cumple las 8 horas mínimas efectivas (se descuenta 1h de almuerzo)`
        break
      }
    }
  }

  errores.value = e
  return Object.keys(e).length === 0
}

async function registrar() {
  errorGeneral.value = ''
  if (!validar()) return
  cargando.value = true

  const diasActivos = diasSemana.value
    .filter(d => d.activo)
    .map(d => ({
      dia:          d.key,
      hora_entrada: d.hora_entrada,
      hora_salida:  d.hora_salida,
      dia_descanso: d.dia_descanso,
    }))

  try {
    await barberoService.registrar({ ...form.value, dias: diasActivos })
    exitoso.value = true
    setTimeout(() => router.push('/admin/barberos'), 1500)
  } catch (err) {
    errorGeneral.value = err.response?.data?.mensaje || 'Error al registrar el barbero'
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.registrar {
  width: 100%;
  max-width: 1200px; /* O el ancho máximo que prefieras */
  margin: 0;         /* Esto quita el centrado automático (el margen izquierdo y derecho) */
  padding: 0 2rem;   /* Agrega un poco de aire a los lados para que no toque los bordes */
}

.registrar__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 2rem;
  gap: 1rem;
}

.registrar__title {
  font-family: var(--font-heading);
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.registrar__subtitle {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.registrar__alerta {
  padding: 0.875rem 1.25rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
}

.registrar__alerta--error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: var(--color-rojo-vintage);
}

.registrar__alerta--exito {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.registrar__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.registrar__seccion {
  padding: 1.75rem;
}

.registrar__seccion-header {
  margin-bottom: 1.5rem;
}

.registrar__seccion-titulo {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 0.375rem;
}

.registrar__seccion-desc {
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.registrar__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.registrar__campo {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.registrar__error {
  font-size: 0.75rem;
  color: var(--color-rojo-vintage);
  margin-top: 0.25rem;
}

.input-field--error {
  border-color: var(--color-rojo-vintage) !important;
  box-shadow: 0 0 0 3px rgba(166, 43, 43, 0.1);
}

/* Días de la semana */
.registrar__dias {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.registrar__dia {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all 0.2s ease;
}

.registrar__dia--activo {
  border-color: var(--color-azul-real);
  box-shadow: 0 2px 8px rgba(22, 62, 113, 0.08);
}

.registrar__dia--inactivo {
  opacity: 0.6;
}

.registrar__dia-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border-light);
}

.registrar__dia-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.registrar__dia-toggle input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--color-azul-real);
  cursor: pointer;
}

.registrar__dia-nombre {
  font-family: var(--font-heading);
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.registrar__dia-horas {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.registrar__dia-horas--ok {
  background: #dcfce7;
  color: #166534;
}

.registrar__dia-horas--mal {
  background: #fee2e2;
  color: var(--color-rojo-vintage);
}

.registrar__dia-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  background: var(--color-oro-suave);
  color: var(--color-bronce);
}

.registrar__dia-body {
  padding: 1rem;
  background: var(--color-bg-primary);
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.registrar__dia-horas-inputs {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
}

.registrar__campo-pequeño {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.registrar__dia-separador {
  font-size: 1rem;
  color: var(--color-bronce);
  padding-bottom: 0.625rem;
}

.registrar__descanso-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.registrar__descanso-toggle input[type="checkbox"] {
  accent-color: var(--color-azul-real);
  cursor: pointer;
}

.registrar__dia-inactivo-msg {
  padding: 0.75rem 1rem;
  font-size: 0.8125rem;
  color: var(--color-text-muted);
  background: var(--color-bg-primary);
  font-style: italic;
}

.registrar__footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 0.5rem;
}

@media (max-width: 640px) {
  .registrar__grid,
  .registrar__dias {
    grid-template-columns: 1fr;
  }
}

.registrar__input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.registrar__input-wrapper .input-field {
  padding-right: 2.75rem;
}

.registrar__ojo {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  line-height: 1;
  color: var(--color-bronce);
  user-select: none;
}  
  
</style>
