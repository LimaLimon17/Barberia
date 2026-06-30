import api from './api.js'

export const agendaService = {
  citasHoy() {
    return api.get('/barbero/agenda/hoy')
  },
  buscarCitas(criterio) {
    return api.get('/barbero/agenda/buscar', { params: { criterio } })
  },
  cambiarEstado(idReserva, estado) {
    return api.put(`/barbero/citas/${idReserva}/estado`, { estado })
  },
}