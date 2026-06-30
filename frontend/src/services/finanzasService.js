import api from './api.js'

export const finanzasService = {
  async getFinanzas(idBarbero = null) {
    const params = {}
    if (idBarbero) params.id_barbero = idBarbero
    const response = await api.get('/admin/finanzas', { params })
    return response.data
  }
}
export default finanzasService
