<template>
  <div class="login-container">
    <div class="login-card glass-card">
      <!-- Logo y título -->
      <div class="login-card__header">
        <img src="/logo.png" alt="Logo Barbería" class="login-card__logo-img" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='inline-block'" />
        <div class="login-card__logo" style="display: none;">✂️</div>
        <h1 class="login-card__title">Barbería</h1>
        <p class="login-card__subtitle">Sistema de Gestión</p>
      </div>

      <!-- Alerta de error -->
      <AlertMessage
        v-if="error"
        :mensaje="error"
        tipo="error"
        :autoClose="false"
        @close="authStore.limpiarError()"
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
        <div class="login__actions" style="text-align: center; margin-top: 1.5rem;">
  <router-link to="/inicio" style="color: var(--color-gold-400); text-decoration: none; font-size: 0.875rem;">
    ← Volver Sitio Público
  </router-link>
</div>
      </form>

      <p class="login-card__footer">
        Solo acceso para personal autorizado
      </p>
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

function limpiarErrorCampo(campo) {
  errores[campo] = ''
  error.value = ''
}

function validarFormulario() {
  let valido = true

  // Escenario 4: Campos vacíos
  if (!form.correo) {
    errores.correo = 'El correo electrónico es obligatorio'
    valido = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo)) {
    errores.correo = 'Ingrese un correo electrónico válido'
    valido = false
  }

  if (!form.contraseña) {
    errores.contraseña = 'La contraseña es obligatoria'
    valido = false
  }

  return valido
}

async function handleLogin() {
  if (!validarFormulario()) return

  try {
    const data = await authStore.login(form.correo, form.contraseña)

    // Escenario 3: Redirección según rol
    if (authStore.esAdmin) {
      router.push({ name: 'DashboardAdmin' })
    } else if (authStore.esBarbero) {
      router.push({ name: 'DashboardBarbero' })
    }
  } catch (err) {
    // Escenario 2: Credenciales incorrectas
    error.value = err.message
  }
}
</script>

<style scoped>
.login-container {
  width: 100%;
  max-width: 420px;
  padding: 1rem;
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
}

.login-card__logo {
  font-size: 3rem;
  margin-bottom: 0.75rem;
  animation: pulse-gold 2s ease infinite;
  display: inline-block;
}

.login-card__logo-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
  margin-bottom: 0.75rem;
  animation: pulse-gold 2s ease infinite;
  border-radius: 50%;
}

.login-card__title {
  font-family: var(--font-heading);
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-300));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.25rem;
}

.login-card__subtitle {
  font-size: 0.875rem;
  color: var(--color-text-muted);
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
}

.input-field--error {
  border-color: var(--color-error) !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
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
  border: 2px solid rgba(15, 15, 19, 0.3);
  border-top-color: var(--color-bg-primary);
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
  color: var(--color-text-muted);
}
</style>
