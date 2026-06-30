import api from './api.js'

const barberoService = {
  async getAll() {
    const response = await api.get('/admin/barberos')
    return response.data
  },
  async getBarberos() {
    return this.getAll()
  },
  async getById(id) {
    const response = await api.get(`/admin/barberos/${id}`)
    return response.data
  },
  async getBarbero(id) {
    return this.getById(id)
  },
  async registrar(datos) {
    const response = await api.post('/admin/barberos', datos)
    return response.data
  },
  async editar(id, datos) {
    const response = await api.put(`/admin/barberos/${id}`, datos)
    return response.data
  },
  async editarBarbero(id, datos) {
    return this.editar(id, datos)
  },
  async desactivar(id) {
    const response = await api.delete(`/admin/barberos/${id}`)
    return response.data
  },

  // ── Horarios (FIFO fijo por antigüedad) ──────────────────────
  async getHorarios(id) {
    const response = await api.get(`/admin/barberos/${id}/horarios`)
    return response.data
  },
  async getHorarioSemana(semana, ano) {
    const response = await api.get(`/admin/horarios-semana?semana=${semana}&ano=${ano}`)
    return response.data
  },
  async generarHorarioSemana(semana, ano) {
    const response = await api.post('/admin/horarios-semana', { semana, ano })
    return response.data
  },
  async reasignarDescanso(idBarbero, { semana, ano, dia_descanso }) {
    const response = await api.put(`/admin/horarios-semana/${idBarbero}/descanso`, { semana, ano, dia_descanso })
    return response.data
  },

  // Métodos de almuerzo ELIMINADOS: getAlmuerzos, registrarSalidaAlmuerzo,
  // registrarRetornoAlmuerzo — esa funcionalidad ya no existe en el negocio.
}

export default barberoService