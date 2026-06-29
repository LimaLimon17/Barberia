import api from './api.js'

export const reportesService = {
  /**
   * Obtener ventas consolidadas (Admin)
   */
  async getVentasAdmin(params) {
    const response = await api.get('/admin/reportes/ventas', { params })
    return response.data
  },
  
  /**
   * Obtener reporte de inventario (Admin)
   */
  async getInventarioAdmin(params) {
    const response = await api.get('/admin/reportes/inventario', { params })
    return response.data
  },

  /**
   * Obtener reporte personalizado del barbero (Barbero)
   */
  async getReporteBarbero(params) {
    const response = await api.get('/barbero/reportes', { params })
    return response.data
  }
}

export default reportesService
