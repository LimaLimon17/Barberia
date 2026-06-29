import { defineStore } from 'pinia'
import { ref } from 'vue'
export const useReservaStore = defineStore('reserva', () => {
  const clienteCI = ref('')
  const barberoSeleccionado = ref(null)
  const serviciosSeleccionados = ref([])
  const fechaCita = ref('')
  const horaInicio = ref('')
  function resetear() {
    clienteCI.value = ''
    barberoSeleccionado.value = null
    serviciosSeleccionados.value = []
    fechaCita.value = ''
    horaInicio.value = ''
  }
  return {
    clienteCI,
    barberoSeleccionado,
    serviciosSeleccionados,
    fechaCita,
    horaInicio,
    resetear,
  }
})