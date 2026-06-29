import api from './api.js'
export const barberoService = {
  /**
   * Obtener perfil del barbero autenticado (solo lectura).
   */
  async getPerfilBarbero() {
    const response = await api.get('/barbero/perfil')
    return response.data
  },
  /**
   * Listar todos los barberos (solo admin).
   */
  async getBarberos() {
    const response = await api.get('/admin/barberos')
    return response.data
  },
  /**
   * Obtener detalle de un barbero con horario (solo admin).
   */
  async getBarbero(id) {
    const response = await api.get(`/admin/barberos/${id}`)
    return response.data
  },
  /**
   * Editar perfil de un barbero (solo admin).
   */
  async editarBarbero(id, datos) {
    const response = await api.put(`/admin/barberos/${id}`, datos)
    return response.data
  },
}
export default barberoService
