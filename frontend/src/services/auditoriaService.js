import api from './api.js'

export const auditoriaService = {
  /**
   * Registra una auditoría cuando se genera o previsualiza un reporte en PDF.
   * @param {string} tipoReporte Nombre o descripción del reporte
   * @param {object} filtros Filtros aplicados en el reporte
   */
  async registrarReporte(tipoReporte, filtros = null) {
    const payload = {
      tipo_reporte: tipoReporte,
      filtros: filtros
    }
    try {
      const response = await api.post('/auditoria/reporte', payload)
      return response.data
    } catch (error) {
      console.error('Error registrando auditoría del reporte:', error)
      // No lanzamos el error para no bloquear la previsualización del PDF en caso de fallo de red
    }
  }
}

export default auditoriaService
