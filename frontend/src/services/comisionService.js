import api from './api.js'

export const comisionService = {
  semana(semana, anio) {
    return api.get('/barbero/comisiones', { params: { semana, anio } })
  },
  filtrar(desde, hasta, cliente) {
    return api.get('/barbero/comisiones/filtrar', { params: { desde, hasta, cliente: cliente || undefined } })
  },
}