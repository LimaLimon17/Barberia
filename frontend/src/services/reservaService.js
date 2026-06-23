import axios from 'axios'

export const reservaService = {
  buscarClientePorCI(ci) {
    return axios.get(`/api/clientes/${ci}`)
  },
  disponibilidadBarberos() {
    return axios.get('/api/barberos/disponibilidad')
  },
  serviciosPorCategoria(idCategoria) {
    return axios.get('/api/servicios', { params: { id_categoria: idCategoria || undefined } })
  },
  slotsDisponibles({ idBarbero, fecha, servicios }) {
    return axios.get('/api/disponibilidad/slots', {
      params: {
        id_barbero: idBarbero,
        fecha,
        servicios,
      },
    })
  },
  crearReserva(payload) {
    return axios.post('/api/reservas', payload)
  },
  confirmarPago(idReserva, metodoPago = 'QR') {
    return axios.post(`/api/reservas/${idReserva}/confirmar-pago`, { metodo_pago: metodoPago })
  },
  consultarEstado(idReserva) {
    return axios.get(`/api/reservas/${idReserva}/estado`)
  },
}
