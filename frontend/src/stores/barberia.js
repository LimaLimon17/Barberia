import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios' 

export const useBarberiaStore = defineStore('barberia', () => {
  const servicios = ref([])
  const barberosActivos = ref([])
  const cargando = ref(false)

  const categorias = ref([])
  const error = ref(null)

  const barberos = computed(() => barberosActivos.value)

//FUNCION PARA CARGAR LOS DATOS PARA LA PAGINA PUBLICA
async function cargarDatosCatalogo() {
    cargando.value = true
    error.value = null
    try {
      const response = await axios.get('/api/catalogo')
      
      // Mapeamos los datos regresados por el controlador público
      categorias.value = response.data.categorias
      servicios.value = response.data.servicios
      
      // Llenamos la variable original. Al actualizarse esta, 
      // la computada 'barberos' se actualizará automáticamente para tu vista.
      barberosActivos.value = response.data.barberos
    } catch (err) {
      console.error('Error al recuperar el catálogo de la barbería:', err)
      error.value = err.message || 'Error de conexión con el servidor.'
    } finally {
      cargando.value = false
    }
  }

  return {
    categorias,
    servicios,
    barberosActivos, 
    barberos,        
    cargando,
    error,
    cargarDatosCatalogo
  }
})