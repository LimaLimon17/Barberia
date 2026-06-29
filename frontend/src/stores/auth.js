import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '../services/authService.js'
import { useRouter } from 'vue-router'
export const useAuthStore = defineStore('auth', () => {
  // State
  const usuario = ref(JSON.parse(localStorage.getItem('usuario') || 'null'))
  const token = ref(localStorage.getItem('auth_token') || null)
  const cargando = ref(false)
  const error = ref(null)
  // Computed
  const isAuthenticated = computed(() => !!token.value)
  const rol = computed(() => usuario.value?.rol?.nombre || null)
  const rolId = computed(() => usuario.value?.rol?.id || null)
  const esAdmin = computed(() => rolId.value === 1)
  const esBarbero = computed(() => rolId.value === 2)
  const nombreCompleto = computed(() => usuario.value?.nombre_completo || '')
  // Actions
  async function login(correo, contraseña) {
    cargando.value = true
    error.value = null
    try {
      const data = await authService.login(correo, contraseña)
      token.value = data.token
      usuario.value = data.usuario
      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('usuario', JSON.stringify(data.usuario))
      return data
    } catch (err) {
      const mensaje = err.response?.data?.mensaje || 'Error al iniciar sesión'
      error.value = mensaje
      throw new Error(mensaje)
    } finally {
      cargando.value = false
    }
  }
  async function logout() {
    try {
      await authService.logout()
    } catch (err) {
      // Continuar con el logout incluso si falla la petición
    } finally {
      token.value = null
      usuario.value = null
      error.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('usuario')
    }
  }
  function limpiarError() {
    error.value = null
  }
  return {
    // State
    usuario,
    token,
    cargando,
    error,
    // Computed
    isAuthenticated,
    rol,
    rolId,
    esAdmin,
    esBarbero,
    nombreCompleto,
    // Actions
    login,
    logout,
    limpiarError,
  }
})
