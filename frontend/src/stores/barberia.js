import { defineStore } from 'pinia'
import { ref } from 'vue'
export const useBarberiaStore = defineStore('barberia', () => {
  const servicios = ref([])
  const barberosActivos = ref([])
  const cargando = ref(false)
  return {
    servicios,
    barberosActivos,
    cargando,
  }
})