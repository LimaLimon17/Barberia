import api from './api.js'

export const citaPresencialService = {
  inicializar() {
    return api.get('/barbero/cita-presencial/inicializar')
  },
  buscarClientePorCI(ci) {
    return api.get(`/barbero/cliente/${ci}`)
  },
  servicios(idCategoria) {
    return api.get('/barbero/cita-presencial/servicios', {
      params: { id_categoria: idCategoria || undefined },
    })
  },
  slots({ fecha, servicios }) {
    return api.get('/barbero/cita-presencial/slots', {
      params: { fecha, servicios },
    })
  },
  crear(payload) {
    return api.post('/barbero/cita-presencial/crear', payload)
  },
  confirmarPago(idReserva) {
    return api.post(`/barbero/cita-presencial/${idReserva}/confirmar-pago`)
  },
  misCitas(desde, hasta) {
    return api.get('/barbero/cita-presencial/citas', { params: { desde, hasta } })
  },
}