import api from './api.js'

export const reservaService = {
  buscarClientePorCI(ci) {
    return api.get(`/clientes/${ci}`)
  },
  disponibilidadBarberos() {
    return api.get('/barberos/disponibilidad')
  },
   categorias() {
    return api.get('/reservas/categorias')
  },
  serviciosPorCategoria(idCategoria) {
    return api.get('/servicios', { params: { id_categoria: idCategoria || undefined } })
  },
  slotsDisponibles({ idBarbero, fecha, servicios }) {
    return api.get('/disponibilidad/slots', {
      params: {
        id_barbero: idBarbero,
        fecha,
        servicios,
      },
    })
  },
  crearReserva(payload) {
    return api.post('/reservas', payload)
  },
  confirmarPago(idReserva, metodoPago = 'QR') {
    return api.post(`/reservas/${idReserva}/confirmar-pago`, { metodo_pago: metodoPago })
  },
  consultarEstado(idReserva) {
    return api.get(`/reservas/${idReserva}/estado`)
  },
}
