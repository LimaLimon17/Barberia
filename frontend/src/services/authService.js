import api from './api.js'

export const authService = {
  /**
   * Login con correo y contraseña.
   * @returns {{ token, usuario }} datos del usuario autenticado
   */
  async login(correo, contraseña) {
    const response = await api.post('/login', { correo, contraseña })
    return response.data
  },

  /**
   * Logout: revoca el token actual.
   */
  async logout() {
    const response = await api.post('/logout')
    return response.data
  },
}

export default authService
