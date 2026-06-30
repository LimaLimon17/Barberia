import api from './api.js'

export const reportesService = {
  async getVentasAdmin(params) {
    const response = await api.get('/admin/reportes/ventas', { params })
    return response.data
  },

  async getInventarioAdmin(params) {
    const response = await api.get('/admin/reportes/inventario', { params })
    return response.data
  },

  async getReporteBarbero(params) {
    const response = await api.get('/barbero/reportes', { params })
    return response.data
  }
}
export default reportesService
