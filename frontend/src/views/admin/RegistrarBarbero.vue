<template>
  <div class="max-w-2xl mx-auto p-6">

    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Registrar nuevo barbero</h1>
      <p class="text-sm text-gray-500 mt-1">Todos los campos marcados con * son obligatorios</p>
    </div>

    <!-- Alerta de error general -->
    <div v-if="errorGeneral" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
      {{ errorGeneral }}
    </div>

    <!-- Alerta de éxito -->
    <div v-if="exitoso" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
      Barbero registrado correctamente. Redirigiendo...
    </div>

    <form @submit.prevent="registrar" class="space-y-6">

      <!-- DATOS PERSONALES -->
      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <h2 class="text-base font-medium text-gray-700 mb-4">Datos personales</h2>

        <div class="grid grid-cols-2 gap-4">

          <div>
            <label class="block text-sm text-gray-600 mb-1">Primer nombre *</label>
            <input
              v-model="form.nombre1"
              type="text"
              placeholder="Ej: Carlos"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
              :class="{ 'border-red-400': errores.nombre1 }"
            />
            <p v-if="errores.nombre1" class="text-red-500 text-xs mt-1">{{ errores.nombre1 }}</p>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Segundo nombre</label>
            <input
              v-model="form.nombre2"
              type="text"
              placeholder="Ej: Andres"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Primer apellido *</label>
            <input
              v-model="form.apellido1"
              type="text"
              placeholder="Ej: Mamani"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
              :class="{ 'border-red-400': errores.apellido1 }"
            />
            <p v-if="errores.apellido1" class="text-red-500 text-xs mt-1">{{ errores.apellido1 }}</p>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Segundo apellido</label>
            <input
              v-model="form.apellido2"
              type="text"
              placeholder="Ej: Quispe"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
            />
          </div>

        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">

          <div>
            <label class="block text-sm text-gray-600 mb-1">Correo electrónico *</label>
            <input
              v-model="form.correo"
              type="email"
              placeholder="correo@ejemplo.com"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
              :class="{ 'border-red-400': errores.correo }"
            />
            <p v-if="errores.correo" class="text-red-500 text-xs mt-1">{{ errores.correo }}</p>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Contraseña *</label>
            <input
              v-model="form.contrasena"
              type="password"
              placeholder="Mínimo 6 caracteres"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
              :class="{ 'border-red-400': errores.contrasena }"
            />
            <p v-if="errores.contrasena" class="text-red-500 text-xs mt-1">{{ errores.contrasena }}</p>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Fecha de ingreso *</label>
            <input
              v-model="form.fecha_ingreso"
              type="date"
              :max="hoy"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
              :class="{ 'border-red-400': errores.fecha_ingreso }"
            />
            <p v-if="errores.fecha_ingreso" class="text-red-500 text-xs mt-1">{{ errores.fecha_ingreso }}</p>
          </div>

        </div>
      </div>

      <!-- HORARIO INICIAL -->
      <div class="bg-white border border-gray-200 rounded-lg p-5">
        <h2 class="text-base font-medium text-gray-700 mb-1">Horario inicial *</h2>
        <p class="text-xs text-gray-400 mb-4">Configure los días que trabajará el barbero. Cada día laboral debe tener mínimo 8 horas efectivas.</p>

        <p v-if="errores.dias" class="text-red-500 text-xs mb-3">{{ errores.dias }}</p>

        <div class="space-y-3">
          <div
            v-for="dia in diasSemana"
            :key="dia.key"
            class="flex items-center gap-3 p-3 border border-gray-100 rounded-lg"
            :class="{ 'bg-gray-50': !dia.activo }"
          >
            <!-- Toggle del día -->
            <input
              type="checkbox"
              v-model="dia.activo"
              class="w-4 h-4 accent-blue-500"
            />
            <span class="text-sm font-medium w-24 text-gray-700">{{ dia.nombre }}</span>

            <!-- Horarios (solo si el día está activo) -->
            <template v-if="dia.activo">
              <div class="flex items-center gap-2 flex-1">
                <input
                  v-model="dia.hora_entrada"
                  type="time"
                  class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-400"
                />
                <span class="text-gray-400 text-sm">a</span>
                <input
                  v-model="dia.hora_salida"
                  type="time"
                  class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-blue-400"
                />
                <span class="text-xs ml-2" :class="horasValidas(dia) ? 'text-green-600' : 'text-red-500'">
                  {{ calcularHoras(dia) }}
                </span>
              </div>

              <label class="flex items-center gap-1 text-xs text-gray-500">
                <input type="checkbox" v-model="dia.dia_descanso" class="accent-orange-400" />
                Día de descanso
              </label>
            </template>

            <span v-else class="text-xs text-gray-400">No trabaja este día</span>
          </div>
        </div>
      </div>

      <!-- BOTONES -->
      <div class="flex gap-3 justify-end">
        <button
          type="button"
          @click="$router.push('/admin/barberos')"
          class="px-5 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="cargando"
          class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ cargando ? 'Registrando...' : 'Registrar barbero' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const router  = useRouter()
const cargando     = ref(false)
const errorGeneral = ref('')
const exitoso      = ref(false)
const errores      = ref({})

const hoy = new Date().toISOString().split('T')[0]

const form = ref({
  nombre1:       '',
  nombre2:       '',
  apellido1:     '',
  apellido2:     '',
  correo:        '',
  contrasena:    '',
  fecha_ingreso: '',
})

const diasSemana = ref([
  { key: 'Lunes',     nombre: 'Lunes',     activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Martes',    nombre: 'Martes',    activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Miércoles', nombre: 'Miércoles', activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Jueves',    nombre: 'Jueves',    activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Viernes',   nombre: 'Viernes',   activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Sábado',    nombre: 'Sábado',    activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Domingo',   nombre: 'Domingo',   activo: false, hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: true  },
])

function calcularHoras(dia) {
  if (!dia.hora_entrada || !dia.hora_salida) return ''
  if (dia.dia_descanso) return 'Descanso'
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
  if (!form.value.nombre1.trim())       e.nombre1       = 'El primer nombre es obligatorio'
  if (!form.value.apellido1.trim())     e.apellido1     = 'El primer apellido es obligatorio'
  if (!form.value.correo.trim())        e.correo        = 'El correo es obligatorio'
  if (!form.value.contrasena.trim())    e.contrasena    = 'La contraseña es obligatoria'
  if (form.value.contrasena.length < 6) e.contrasena    = 'Mínimo 6 caracteres'
  if (!form.value.fecha_ingreso)        e.fecha_ingreso = 'La fecha de ingreso es obligatoria'

  const diasActivos = diasSemana.value.filter(d => d.activo)
  if (diasActivos.length === 0) e.dias = 'Debe activar al menos un día de trabajo'

  const diaInvalido = diasActivos.find(d => !d.dia_descanso && !horasValidas(d))
  if (diaInvalido) e.dias = `El día ${diaInvalido.nombre} no cumple las 8 horas mínimas`

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
    await barberoService.registrar({
      ...form.value,
      dias: diasActivos,
    })

    exitoso.value = true
    setTimeout(() => router.push('/admin/barberos'), 1500)

  } catch (err) {
    const mensaje = err.response?.data?.mensaje || 'Error al registrar el barbero'
    errorGeneral.value = mensaje
  } finally {
    cargando.value = false
  }
}
</script>
