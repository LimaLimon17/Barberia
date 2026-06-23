import api from './api.js'

const barberoService = {

  // Obtener lista de todos los barberos
  async getAll() {
    const response = await api.get('/admin/barberos')
    return response.data
  },

  // Alias para compatibilidad con ListaBarberos existente
  async getBarberos() {
    return this.getAll()
  },

  // Obtener un barbero por ID
  async getById(id) {
    const response = await api.get(`/admin/barberos/${id}`)
    return response.data
  },

  // Alias para compatibilidad con PerfilBarberoAdmin y EditarBarbero existentes
  async getBarbero(id) {
    return this.getById(id)
  },

  // Registrar nuevo barbero con horario inicial
  async registrar(datos) {
    const response = await api.post('/admin/barberos', datos)
    return response.data
  },

  // Editar datos del barbero
  async editar(id, datos) {
    const response = await api.put(`/admin/barberos/${id}`, datos)
    return response.data
  },

  // Alias para compatibilidad con EditarBarbero existente
  async editarBarbero(id, datos) {
    return this.editar(id, datos)
  },

  // Desactivar barbero
  async desactivar(id) {
    const response = await api.delete(`/admin/barberos/${id}`)
    return response.data
  },

  // Obtener horarios de un barbero
  async getHorarios(id) {
    const response = await api.get(`/admin/barberos/${id}/horarios`)
    return response.data
  },

  // Crear nuevo horario semanal
  async crearHorario(datos) {
    const response = await api.post('/admin/horarios', datos)
    return response.data
  },

}

export default barberoService
