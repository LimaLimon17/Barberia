// services/pagoFinalService.js
import api from './api.js'

export const pagoFinalService = {
  resumen(idReserva) {
    return api.get(`/barbero/citas/${idReserva}/pago-final/resumen`)
  },
  iniciar(idReserva, metodoPago) {
    return api.post(`/barbero/citas/${idReserva}/pago-final`, { metodo_pago: metodoPago })
  },
  confirmar(idReserva) {
    return api.post(`/barbero/citas/${idReserva}/pago-final/confirmar`)
  },
}