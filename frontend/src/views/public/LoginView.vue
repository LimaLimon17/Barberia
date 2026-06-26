<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-card glass-card">
        <!-- Logo centrado -->
        <div class="login-card__header">
          <div class="login-card__logo-wrapper">
            <img src="/logo.png" alt="Logo Barbería" class="login-card__logo-img" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'" />
            <div class="login-card__logo-fallback" style="display: none;">✂️</div>
          </div>
          <h1 class="login-card__title">Barbería</h1>
          <p class="login-card__subtitle">Sistema de Gestión</p>
        </div>

        <!-- Alerta de éxito -->
        <AlertMessage
          v-if="mensajeExito"
          :mensaje="mensajeExito"
          tipo="success"
          :duracion="3000"
          @close="mensajeExito = ''"
        />

        <!-- Alerta de error -->
        <AlertMessage
          v-if="error"
          :mensaje="error"
          tipo="error"
          :autoClose="false"
          @close="error = ''"
        />

        <!-- Formulario de login -->
        <form id="form-login" class="login-form" @submit.prevent="handleLogin" novalidate>
          <div class="login-form__group">
            <label class="label" for="input-correo">Correo Electrónico</label>
            <input
              id="input-correo"
              type="email"
              class="input-field"
              :class="{ 'input-field--error': errores.correo }"
              v-model.trim="form.correo"
              placeholder="correo@barberia.com"
              autocomplete="email"
              required
              @input="limpiarErrorCampo('correo')"
            />
            <span v-if="errores.correo" class="login-form__error">{{ errores.correo }}</span>
          </div>

          <div class="login-form__group">
            <label class="label" for="input-password">Contraseña</label>
            <div class="login-form__password-wrapper">
              <input
                id="input-password"
                :type="mostrarPassword ? 'text' : 'password'"
                class="input-field"
                :class="{ 'input-field--error': errores.contraseña }"
                v-model="form.contraseña"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                @input="limpiarErrorCampo('contraseña')"
              />
              <button
                type="button"
                class="login-form__toggle-pw"
                @click="mostrarPassword = !mostrarPassword"
                :title="mostrarPassword ? 'Ocultar' : 'Mostrar'"
              >
                {{ mostrarPassword ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.contraseña" class="login-form__error">{{ errores.contraseña }}</span>
          </div>

          <button
            id="btn-login"
            type="submit"
            class="btn-primary login-form__submit"
            :disabled="authStore.cargando"
          >
            <span v-if="authStore.cargando" class="login-form__spinner"></span>
            <span v-else>Ingresar</span>
          </button>
        </form>

        <p class="login-card__footer">
          Solo acceso para personal autorizado
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import AlertMessage from '../../components/common/AlertMessage.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  correo: '',
  contraseña: '',
})

const errores = reactive({
  correo: '',
  contraseña: '',
})

const mostrarPassword = ref(false)
const error = ref('')
const mensajeExito = ref('')

function limpiarErrorCampo(campo) {
  errores[campo] = ''
  error.value = ''
}

function validarFormulario() {
  let valido = true

  // Campos vacíos (obligatorios)
  if (!form.correo) {
    errores.correo = 'Complete este campo'
    valido = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo)) {
    errores.correo = 'Ingrese un correo electrónico válido'
    valido = false
  }

  if (!form.contraseña) {
    errores.contraseña = 'Complete este campo'
    valido = false
  }

  return valido
}

async function handleLogin() {
  error.value = ''
  mensajeExito.value = ''

  if (!validarFormulario()) return

  try {
    await authStore.login(form.correo, form.contraseña)

    // Mensaje de inicio de sesión exitoso
    mensajeExito.value = 'Inicio de sesión exitoso. Redirigiendo...'

    // Redirección según rol después de un breve delay para que vea el mensaje
    setTimeout(() => {
      if (authStore.esAdmin) {
        router.push({ name: 'DashboardAdmin' })
      } else if (authStore.esBarbero) {
        router.push({ name: 'DashboardBarbero' })
      }
    }, 1000)
  } catch (err) {
    // Credenciales incorrectas
    error.value = 'Correo o contraseña incorrectos. Verifique sus credenciales.'
  }
}
</script>

<style scoped>
.login-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--color-azul-oscuro) 0%, var(--color-azul-real) 100%);
  padding: 1rem;
}

.login-container {
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
}

.login-card {
  padding: 2.5rem;
  animation: fadeIn 0.6s ease-out;
}

.login-card__header {
  text-align: center;
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.login-card__logo-wrapper {
  margin-bottom: 0.75rem;
}

.login-card__logo-img {
  width: 90px;
  height: 90px;
  object-fit: contain;
  animation: pulse-gold 2s ease infinite;
  border-radius: 50%;
  display: block;
}

.login-card__logo-fallback {
  width: 90px;
  height: 90px;
  font-size: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse-gold 2s ease infinite;
}

.login-card__title {
  font-family: var(--font-heading);
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-azul-oscuro);
  margin-bottom: 0.25rem;
}

.login-card__subtitle {
  font-size: 0.875rem;
  color: var(--color-bronce);
  letter-spacing: 0.05em;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.login-form__group {
  display: flex;
  flex-direction: column;
}

.login-form__password-wrapper {
  position: relative;
}

.login-form__password-wrapper .input-field {
  padding-right: 3rem;
}

.login-form__toggle-pw {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.125rem;
  padding: 0.25rem;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.login-form__toggle-pw:hover {
  opacity: 1;
}

.login-form__error {
  font-size: 0.75rem;
  color: var(--color-error);
  margin-top: 0.25rem;
  font-weight: 500;
}

.input-field--error {
  border-color: var(--color-error) !important;
  box-shadow: 0 0 0 3px rgba(166, 43, 43, 0.1);
}

.login-form__submit {
  width: 100%;
  margin-top: 0.5rem;
  padding: 0.875rem;
  font-size: 1rem;
}

.login-form__spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.login-card__footer {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.75rem;
  color: var(--color-bronce);
}
</style>
