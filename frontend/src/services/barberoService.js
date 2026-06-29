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

  // ¡CORREGIDO! Se cambió api.put por api.patch para alinearse con Laravel
  async activar(id) {
    const response = await api.patch(`/admin/barberos/${id}/activar`)
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

  // Obtener estado de horarios de una semana
  async getHorarioSemana(semana, ano) {
    const response = await api.get(`/admin/horarios-semana?semana=${semana}&ano=${ano}`)
    return response.data
  },

  // Generar horarios de la semana (FIFO + rotación almuerzo)
  async generarHorarioSemana(semana, ano) {
    const response = await api.post('/admin/horarios-semana', { semana, ano })
    return response.data
  },

  // Actualizar horario existente
  async actualizarHorario(idHorarioSemanal, dias) {
    const response = await api.put(`/admin/horarios/${idHorarioSemanal}`, { dias })
    return response.data
  },

  // Ver registros de almuerzo de un barbero
  async getAlmuerzos(idBarbero, fechaInicio = null, fechaFin = null) {
    let url = `/admin/barberos/${idBarbero}/almuerzos`
    if (fechaInicio && fechaFin) url += `?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`
    const response = await api.get(url)
    return response.data
  },

  // Registrar salida a almuerzo
  async registrarSalidaAlmuerzo(idBarbero, datos) {
    const response = await api.post(`/admin/barberos/${idBarbero}/almuerzos`, datos)
    return response.data
  },

  // Registrar retorno de almuerzo
  async registrarRetornoAlmuerzo(idBarbero, idRegistro, horaRetorno) {
    const response = await api.put(
      `/admin/barberos/${idBarbero}/almuerzos/${idRegistro}`,
      { hora_retorno: horaRetorno }
    )
    return response.data
  },

}

export default barberoService