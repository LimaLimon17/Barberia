import api from './api.js'

export const auditoriaService = {
  async registrarReporte(tipoReporte, filtros = null) {
    const payload = {
      tipo_reporte: tipoReporte,
      filtros: filtros
    }
    try {
      const response = await api.post('/auditoria/reporte', payload)
      return response.data
    } catch (error) {
      console.error('Error registrando auditoría:', error)
    }
  }
}
export default auditoriaService
