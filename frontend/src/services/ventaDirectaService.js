// services/ventaDirectaService.js
import api from './api.js'

export const ventaDirectaService = {
  iniciar(payload) {
    return api.post('/barbero/venta-directa', payload)
  },
  confirmar(payload) {
    return api.post('/barbero/venta-directa/confirmar', payload)
  },
}