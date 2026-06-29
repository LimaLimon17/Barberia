<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
      <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
      
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      
      <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
        <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white" id="modal-title">
                Transacción Exitosa
              </h3>
              <div class="mt-2">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  La transacción se ha registrado correctamente. Es obligatorio generar e imprimir la nota de venta (comprobante simplificado).
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 sm:px-6 flex justify-end gap-3">
          <button @click="generarPDF" type="button" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm transition-colors">
            Generar Nota de Venta PDF
          </button>
          <!-- Se puede añadir un boton cerrar opcional, pero RF21 dice que debe imprimir obligatoriamente, asi que cerrar solo se habilita despues de generar -->
          <button v-if="pdfGenerado" @click="cerrar" type="button" class="inline-flex justify-center rounded-lg border border-slate-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none sm:text-sm transition-colors">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { pdfGenerator } from '../../utils/pdfGenerator'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  transaccion: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])
const pdfGenerado = ref(false)

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    pdfGenerado.value = false
  }
})

const generarPDF = () => {
  // autoPrint = true para que el navegador intente abrir dialogo de impresion
  const url = pdfGenerator.exportarNotaVenta(props.transaccion, true)
  
  // Abrir en nueva pestaña
  window.open(url, '_blank')
  
  pdfGenerado.value = true
}

const cerrar = () => {
  emit('close')
}
</script>
