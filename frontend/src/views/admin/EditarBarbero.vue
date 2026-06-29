<template>
  <div class="editar animate-fade-in">
    <div class="editar__header">
      <div class="editar__back">
        <router-link :to="`/admin/barberos/${id}`" class="btn-secondary editar__back-btn">
          ← Volver al perfil
        </router-link>
        <h1 class="editar__title">Editar Barbero</h1>
      </div>
    </div>
    <!-- Loading -->
    <div v-if="cargandoInicial" class="editar__loading">
      <div class="editar__spinner"></div>
      <p>Cargando datos...</p>
    </div>
    <!-- Alertas -->
    <AlertMessage v-if="mensajeExito" :mensaje="mensajeExito" tipo="success" @close="mensajeExito = ''" />
    <AlertMessage v-if="mensajeError" :mensaje="mensajeError" tipo="error" :autoClose="false" @close="mensajeError = ''" />
    <!-- Formulario de edición -->
    <form
      v-if="!cargandoInicial && form"
      id="form-editar-barbero"
      class="editar__form glass-card"
      @submit.prevent="guardarCambios"
      novalidate
    >
      <h3 class="editar__section-title">👤 Información Personal</h3>
      <h3 class="editar__section-title">👤 Editar Información Personal</h3>
      <div class="editar__grid">
        <div class="editar__group">
          <label class="label" for="input-nombre1">Primer Nombre *</label>
          <input
            id="input-nombre1"
            type="text"
            class="input-field"
            :class="{ 'input-field--error': errores.nombre1 }"
            v-model.trim="form.nombre1"
            placeholder="Nombre"
            @input="limpiarError('nombre1')"
            required
          />
          <span v-if="errores.nombre1" class="editar__error">{{ errores.nombre1 }}</span>
        </div>
        <div class="editar__group">
          <label class="label" for="input-nombre2">Segundo Nombre</label>
          <input
            id="input-nombre2"
            type="text"
            class="input-field"
            v-model.trim="form.nombre2"
            placeholder="Segundo nombre (opcional)"
          />
        </div>
        <div class="editar__group">
          <label class="label" for="input-apellido1">Primer Apellido *</label>
          <input
            id="input-apellido1"
            type="text"
            class="input-field"
            :class="{ 'input-field--error': errores.apellido1 }"
            v-model.trim="form.apellido1"
            placeholder="Apellido"
            @input="limpiarError('apellido1')"
            required
          />
          <span v-if="errores.apellido1" class="editar__error">{{ errores.apellido1 }}</span>
        </div>
        <div class="editar__group">
          <label class="label" for="input-apellido2">Segundo Apellido</label>
          <input
            id="input-apellido2"
            type="text"
            class="input-field"
            v-model.trim="form.apellido2"
            placeholder="Segundo apellido (opcional)"
          />
        </div>
        <div class="editar__group">
          <label class="label" for="input-correo">Correo Electrónico *</label>
          <input
            id="input-correo"
            type="email"
            class="input-field"
            :class="{ 'input-field--error': errores.correo }"
            v-model.trim="form.correo"
            placeholder="correo@barberia.com"
            @input="limpiarError('correo')"
            required
          />
          <span v-if="errores.correo" class="editar__error">{{ errores.correo }}</span>
        </div>
        <div class="editar__group">
          <label class="label" for="input-fecha">Fecha de Ingreso *</label>
          <input
            id="input-fecha"
            type="date"
            class="input-field"
            :class="{ 'input-field--error': errores.fecha_ingreso }"
            v-model="form.fecha_ingreso"
            :max="fechaMaxima"
            @input="limpiarError('fecha_ingreso')"
            required
          />
          <span v-if="errores.fecha_ingreso" class="editar__error">{{ errores.fecha_ingreso }}</span>
        </div>
      </div>
      <!-- Antigüedad calculada en tiempo real -->
      <div class="editar__antiguedad" v-if="form.fecha_ingreso">
        <span class="editar__antiguedad-label">Antigüedad calculada:</span>
        <span class="editar__antiguedad-label">Antigüedad recalculada:</span>
        <span class="editar__antiguedad-valor">{{ antiguedadCalculada }} días</span>
      </div>
      <div class="editar__footer">
        <router-link :to="`/admin/barberos/${id}`" class="btn-secondary">
          Cancelar
        </router-link>
        <button
          id="btn-guardar"
          type="submit"
          class="btn-primary"
          :disabled="guardando"
        >
          <span v-if="guardando" class="editar__btn-spinner"></span>
          <span v-else>💾 Guardar Cambios</span>
        </button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { calcularAntiguedad } from '../../utils/helpers.js'
const route = useRoute()
const id = route.params.id
const form = reactive({
  nombre1: '',
  nombre2: '',
  apellido1: '',
  apellido2: '',
  correo: '',
  fecha_ingreso: '',
})
const errores = reactive({
  nombre1: '',
  apellido1: '',
  correo: '',
  fecha_ingreso: '',
})
const cargandoInicial = ref(true)
const guardando = ref(false)
const mensajeExito = ref('')
const mensajeError = ref('')
const fechaMaxima = computed(() => {
  return new Date().toISOString().split('T')[0]
})
const antiguedadCalculada = computed(() => {
  return calcularAntiguedad(form.fecha_ingreso)
})
function limpiarError(campo) {
  errores[campo] = ''
  mensajeError.value = ''
  mensajeExito.value = ''
}
function validarFormulario() {
  let valido = true
  // Campos obligatorios
  if (!form.nombre1) {
    errores.nombre1 = 'El primer nombre es obligatorio'
    valido = false
  }
  if (!form.apellido1) {
    errores.apellido1 = 'El primer apellido es obligatorio'
    valido = false
  }
  if (!form.correo) {
    errores.correo = 'El correo electrónico es obligatorio'
    valido = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo)) {
    errores.correo = 'Ingrese un correo electrónico válido'
    valido = false
  }
  // Validación de fecha
  if (!form.fecha_ingreso) {
    errores.fecha_ingreso = 'La fecha de ingreso es obligatoria'
    valido = false
  } else if (form.fecha_ingreso > fechaMaxima.value) {
    // Escenario 4: Fecha futura
    // Escenario: Fecha posterior a la actual
    errores.fecha_ingreso = 'La fecha de ingreso no puede ser posterior a la fecha actual'
    valido = false
  }
  return valido
}
async function guardarCambios() {
  mensajeExito.value = ''
  mensajeError.value = ''
  if (!validarFormulario()) return
  guardando.value = true
  try {
    const data = await barberoService.editarBarbero(id, {
      nombre1: form.nombre1,
      nombre2: form.nombre2,
      apellido1: form.apellido1,
      apellido2: form.apellido2,
      correo: form.correo,
      fecha_ingreso: form.fecha_ingreso,
    })
    // Escenario 5: Confirmación de cambios
    // Escenario: Confirmación de cambios exitosa
    mensajeExito.value = 'Perfil del barbero actualizado correctamente'
    // Actualizar formulario con datos retornados
    if (data.barbero) {
      form.nombre1 = data.barbero.nombre1
      form.nombre2 = data.barbero.nombre2
      form.apellido1 = data.barbero.apellido1
      form.apellido2 = data.barbero.apellido2
      form.correo = data.barbero.correo
      form.fecha_ingreso = data.barbero.fecha_ingreso
    }
  } catch (err) {
    // Escenario 3: Correo duplicado / Escenario 4: Fecha futura
    // Escenarios de error (Correo duplicado o fecha inválida lanzados por BD/API)
    const mensaje = err.response?.data?.mensaje || 'Error al guardar los cambios'
    if (mensaje.includes('correo')) {
      errores.correo = mensaje
    } else if (mensaje.includes('fecha')) {
      errores.fecha_ingreso = mensaje
    } else {
      mensajeError.value = mensaje
    }
  } finally {
    guardando.value = false
  }
}
onMounted(async () => {
  try {
    const data = await barberoService.getBarbero(id)
    const b = data.barbero
    form.nombre1 = b.nombre1
    form.nombre2 = b.nombre2 || ''
    form.apellido1 = b.apellido1
    form.apellido2 = b.apellido2 || ''
    form.correo = b.correo
    form.fecha_ingreso = b.fecha_ingreso
  } catch (err) {
    mensajeError.value = err.response?.data?.mensaje || 'Error al cargar los datos del barbero'
  } finally {
    cargandoInicial.value = false
  }
})
</script>
<style scoped>
.editar {
  max-width: 800px;
}
.editar__header {
  margin-bottom: 1.5rem;
}
.editar__back {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.editar__back-btn {
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
}
.editar__title {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 700;
}
.editar__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}
.editar__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold-400);
  border-top-color: var(--color-azul-real);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.editar__form {
  padding: 2rem;
}
.editar__section-title {
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-gold-400);
  color: var(--color-azul-real);
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border);
}
.editar__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.25rem;
}
.editar__group {
  display: flex;
  flex-direction: column;
}
.editar__error {
  font-size: 0.75rem;
  color: var(--color-error);
  margin-top: 0.25rem;
}
.input-field--error {
  border-color: var(--color-error) !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
  box-shadow: 0 0 0 3px rgba(166, 43, 43, 0.1);
}
.editar__antiguedad {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding: 0.875rem 1.25rem;
  background: rgba(232, 184, 17, 0.08);
  border: 1px solid rgba(232, 184, 17, 0.15);
  background: var(--color-oro-suave);
  border: 1px solid var(--color-bronce);
  border-radius: var(--radius-md);
}
.editar__antiguedad-label {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  color: var(--color-azul-oscuro);
}
.editar__antiguedad-valor {
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-gold-400);
  color: var(--color-azul-real);
}
.editar__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
}
.editar__btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(15, 15, 19, 0.3);
  border-top-color: var(--color-bg-primary);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
</style>
