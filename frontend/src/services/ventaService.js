import api from './api.js'

export const ventaService = {
  productosDisponibles() {
    return api.get('/barbero/productos')
  },
  ventaDeLaCita(idReserva) {
    return api.get(`/barbero/citas/${idReserva}/venta`)
  },
  agregarProductos(idReserva, productos) {
    return api.post(`/barbero/citas/${idReserva}/venta`, { productos })
  },
}