<template>
  <div class="login-modern-wrapper">
    <!-- Mitad Decorativa (Solo visible en pantallas grandes) -->
    <div class="login-modern__brand">
      <div class="login-modern__brand-content">
        <div class="login-modern__logo-box">
          <img
            src="/logo.png"
            alt="The Lamplight Barber Shop"
            class="login-modern__logo"
            @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'"
          />
          <div class="login-modern__logo-fallback" style="display:none;">
            ✂️
          </div>
        </div>
        <h1 class="login-modern__hero-title">The Lamplight</h1>
        <h2 class="login-modern__hero-subtitle">Barber Shop</h2>
        
        <div class="login-modern__pole-accent">
          <div class="pole-stripe pole-stripe--red"></div>
          <div class="pole-stripe pole-stripe--white"></div>
          <div class="pole-stripe pole-stripe--blue"></div>
          <div class="pole-stripe pole-stripe--white"></div>
          <div class="pole-stripe pole-stripe--red"></div>
        </div>
        
        <p class="login-modern__hero-text">
          Sistema de gestión integral para profesionales del estilo.
        </p>
      </div>
      <!-- Resplandor decorativo -->
      <div class="login-modern__glow"></div>
    </div>

    <!-- Mitad del Formulario -->
    <div class="login-modern__form-section">
      <div class="login-modern__form-container">
        
        <div class="login-modern__mobile-header">
          <h2 class="login-modern__mobile-title">The Lamplight</h2>
          <p class="login-modern__mobile-subtitle">Barber Shop</p>
        </div>

        <div class="login-modern__welcome">
          <h3>Bienvenido de vuelta</h3>
          <p>Ingresa tus credenciales para acceder a tu panel.</p>
        </div>

        <!-- Alertas -->
        <AlertMessage
          v-if="error"
          :mensaje="error"
          tipo="error"
          :autoClose="false"
          @close="error = ''"
        />

        <AlertMessage
          v-if="mensajeExito"
          :mensaje="mensajeExito"
          tipo="success"
          :duracion="3000"
          @close="mensajeExito = ''"
        />

        <!-- Formulario -->
        <form
          id="form-login"
          class="login-modern__form"
          @submit.prevent="handleLogin"
          novalidate
        >
          <!-- Grupo Correo (Estilo Moderno) -->
          <div class="login-modern__input-group">
            <input
              id="input-correo"
              type="email"
              class="login-modern__input"
              :class="{ 'login-modern__input--error': errores.correo }"
              v-model.trim="form.correo"
              placeholder=" "
              autocomplete="email"
              required
              @input="limpiarErrorCampo('correo')"
            />
            <label for="input-correo" class="login-modern__floating-label">
              Correo Electrónico
            </label>
            <span v-if="errores.correo" class="login-modern__error-text">
              {{ errores.correo }}
            </span>
          </div>

          <!-- Grupo Contraseña (Estilo Moderno) -->
          <div class="login-modern__input-group">
            <div class="login-modern__password-wrapper">
              <input
                id="input-password"
                :type="mostrarPassword ? 'text' : 'password'"
                class="login-modern__input"
                :class="{ 'login-modern__input--error': errores.contraseña }"
                v-model="form.contraseña"
                placeholder=" "
                autocomplete="current-password"
                required
                @input="limpiarErrorCampo('contraseña')"
              />
              <label for="input-password" class="login-modern__floating-label">
                Contraseña
              </label>
              <button
                type="button"
                class="login-modern__toggle-pw"
                @click="mostrarPassword = !mostrarPassword"
                :title="mostrarPassword ? 'Ocultar' : 'Mostrar'"
              >
                {{ mostrarPassword ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.contraseña" class="login-modern__error-text">
              {{ errores.contraseña }}
            </span>
          </div>

          <!-- Botón de Login -->
          <button
            id="btn-login"
            type="submit"
            class="login-modern__btn"
            :disabled="authStore.cargando"
          >
            <span v-if="authStore.cargando" class="login-modern__spinner"></span>
            <span v-else class="login-modern__btn-text">
              Ingresar al Sistema
              <span class="login-modern__btn-icon">→</span>
            </span>
          </button>
        </form>

        <p class="login-modern__footer-note">
          🔒 Acceso restringido a personal autorizado.
        </p>
      </div>

      <!-- ── AGREGADO: Enlace para Volver al Sitio Público ── -->
      <div class="login-back-container">
        <router-link :to="{ name: 'Home' }" class="login-back-link">
          ← Volver al Sitio Público
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import AlertMessage from '../../components/common/AlertMessage.vue'

const router = useRouter()
const authStore = useAuthStore()

const error = ref('')
const mensajeExito = ref('')
const mostrarPassword = ref(false)

const form = reactive({
  correo: '',
  contraseña: ''
})

const errores = reactive({
  correo: '',
  contraseña: ''
})

function limpiarErrorCampo(campo) {
  errores[campo] = ''
}

async function handleLogin() {
  error.value = ''
  mensajeExito.value = ''

  if (!form.correo) {
    errores.correo = 'Complete este campo'
  }

  if (!form.contraseña) {
    errores.contraseña = 'Complete este campo'
  }

  if (errores.correo || errores.contraseña) {
    return
  }

  try {
    const data = await authStore.login(form.correo, form.contraseña)
    mensajeExito.value = data.mensaje || 'Inicio de sesión exitoso'
    
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
/* =========================================
   LOGIN MODERNO (TEMA VINTAGE PREMIUM)
   ========================================= */

.login-modern-wrapper {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background-color: var(--color-crema);
}

/* --- Mitad Izquierda (Marca/Branding) --- */
.login-modern__brand {
  display: none;
  position: relative;
  width: 45%;
  background-color: var(--color-azul-oscuro);
  color: var(--color-crema);
  overflow: hidden;
  align-items: center;
  justify-content: center;
  padding: 4rem;
}

@media (min-width: 1024px) {
  .login-modern__brand {
    display: flex;
  }
}

.login-modern__brand-content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 400px;
}

.login-modern__logo-box {
  width: 120px;
  height: 120px;
  margin: 0 auto 2rem;
  border-radius: 50%;
  background: var(--color-bg-glass);
  padding: 10px;
  border: 2px solid var(--color-bronce);
  box-shadow: 0 0 20px rgba(208, 194, 167, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-modern__logo {
  max-width: 100%;
  height: auto;
  border-radius: 50%;
}

.login-modern__logo-fallback {
  font-size: 3rem;
}

.login-modern__hero-title {
  font-family: var(--font-heading);
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--color-crema);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.login-modern__hero-subtitle {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 400;
  color: var(--color-oro-suave);
  margin-bottom: 2rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}

.login-modern__pole-accent {
  display: flex;
  height: 6px;
  width: 100%;
  max-width: 200px;
  margin: 0 auto 2rem;
  border-radius: 3px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.pole-stripe {
  flex: 1;
  transform: skewX(-45deg);
  margin: 0 -2px;
}

.pole-stripe--red { background-color: var(--color-rojo-vintage); }
.pole-stripe--white { background-color: #FFFFFF; }
.pole-stripe--blue { background-color: var(--color-azul-real); }

.login-modern__hero-text {
  font-size: 1.125rem;
  color: rgba(246, 243, 234, 0.8);
  line-height: 1.6;
}

.login-modern__glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(208,194,167,0.15) 0%, rgba(13,30,45,0) 70%);
  z-index: 1;
}

/* --- Mitad Derecha (Formulario) --- */
.login-modern__form-section {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 2rem;
}

@media (min-width: 1024px) {
  .login-modern__form-section {
    width: 55%;
  }
}

.login-modern__form-container {
  width: 100%;
  max-width: 420px;
}

.login-modern__mobile-header {
  text-align: center;
  margin-bottom: 2rem;
  display: block;
}

@media (min-width: 1024px) {
  .login-modern__mobile-header {
    display: none;
  }
}

.login-modern__mobile-title {
  font-family: var(--font-heading);
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--color-azul-oscuro);
  line-height: 1.2;
}

.login-modern__mobile-subtitle {
  font-size: 1rem;
  color: var(--color-rojo-vintage);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 600;
}

.login-modern__welcome {
  margin-bottom: 2.5rem;
}

.login-modern__welcome h3 {
  font-size: 1.75rem;
  color: var(--color-azul-oscuro);
  margin-bottom: 0.5rem;
}

.login-modern__welcome p {
  color: var(--color-bronce);
  font-size: 1rem;
}

.login-modern__form {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

/* --- Inputs con Floating Labels --- */
.login-modern__input-group {
  position: relative;
  display: flex;
  flex-direction: column;
}

.login-modern__password-wrapper {
  position: relative;
  display: flex;
}

.login-modern__input {
  width: 100%;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  color: var(--color-azul-oscuro);
  background-color: transparent;
  border: 2px solid var(--color-bronce);
  border-radius: var(--radius-md);
  outline: none;
  transition: all 0.3s ease;
}

.login-modern__input:focus {
  border-color: var(--color-azul-real);
  box-shadow: 0 4px 12px rgba(26, 70, 140, 0.1);
}

.login-modern__input--error {
  border-color: var(--color-rojo-vintage) !important;
}

.login-modern__floating-label {
  position: absolute;
  left: 1rem;
  top: 1.1rem;
  color: var(--color-bronce);
  font-size: 1rem;
  pointer-events: none;
  transition: 0.2s ease all;
  background-color: var(--color-crema);
  padding: 0 0.25rem;
}

/* Efecto de Floating Label */
.login-modern__input:focus ~ .login-modern__floating-label,
.login-modern__input:not(:placeholder-shown) ~ .login-modern__floating-label {
  top: -0.6rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-azul-real);
}

.login-modern__input--error:focus ~ .login-modern__floating-label,
.login-modern__input--error:not(:placeholder-shown) ~ .login-modern__floating-label {
  color: var(--color-rojo-vintage);
}

.login-modern__toggle-pw {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  opacity: 0.6;
  transition: opacity 0.3s;
}

.login-modern__toggle-pw:hover {
  opacity: 1;
}

.login-modern__error-text {
  font-size: 0.8125rem;
  color: var(--color-rojo-vintage);
  margin-top: 0.375rem;
  font-weight: 500;
}

/* --- Botón --- */
.login-modern__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 1rem;
  background: var(--color-azul-oscuro);
  color: #fff;
  font-family: var(--font-heading);
  font-size: 1.125rem;
  font-weight: 600;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1rem;
}

.login-modern__btn:hover:not(:disabled) {
  background: var(--color-azul-real);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(26, 70, 140, 0.2);
}

.login-modern__btn:active:not(:disabled) {
  transform: translateY(0);
}

.login-modern__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.login-modern__btn-text {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.login-modern__btn-icon {
  font-size: 1.25rem;
  transition: transform 0.3s;
}

.login-modern__btn:hover:not(:disabled) .login-modern__btn-icon {
  transform: translateX(4px);
}

.login-modern__spinner {
  width: 24px;
  height: 24px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.login-modern__footer-note {
  margin-top: 2rem;
  text-align: center;
  font-size: 0.875rem;
  color: var(--color-bronce);
}

/* ── AGREGADO: Estilos para el enlace de regreso al sitio público ── */
.login-back-container {
  text-align: center;
  margin-top: 1.5rem;
  animation: fadeIn 0.8s ease-out;
}

.login-back-link {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 4px;
}

.login-back-link:hover {
  color: #ffffff;
  text-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
  transform: translateX(-3px);
}
</style>