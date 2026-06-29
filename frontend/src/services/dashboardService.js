import api from './api.js'

export const dashboardService = {
  /**
   * Obtener datos consolidados del dashboard de administrador
   */
  async getDashboardAdmin() {
    const response = await api.get('/admin/dashboard')
    return response.data
  }
}

export default dashboardService
