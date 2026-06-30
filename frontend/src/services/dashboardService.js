import api from './api.js'

export const dashboardService = {
  async getDashboardAdmin() {
    const response = await api.get('/admin/dashboard')
    return response.data
  }
}
export default dashboardService
