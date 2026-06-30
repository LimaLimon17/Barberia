import api from './api.js'

const perfilBarberoService = {
  async getPerfilBarbero() {
    const response = await api.get('/barbero/perfil')
    return response.data
  },
  async cambiarPassword(passwordActual, passwordNueva, passwordNuevaConfirmation) {
    const response = await api.put('/barbero/perfil/cambiar-password', {
      password_actual: passwordActual,
      password_nueva: passwordNueva,
      password_nueva_confirmation: passwordNuevaConfirmation,
    })
    return response.data
  },
}

export default perfilBarberoService