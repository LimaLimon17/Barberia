<template>
  <div class="modal-overlay" v-if="isOpen" @click.self="$emit('close')">
    <div class="modal glass-card animate-fade-in">
      <div class="modal__header">
        <h2 class="modal__title">🧾 Nota de Venta</h2>
        <button class="modal__close" @click="$emit('close')">✕</button>
      </div>
      <div class="modal__body">
        <p class="modal__desc">Se abrirá la nota de venta en una nueva pestaña para imprimir o descargar.</p>

        <div class="modal__preview">
          <div class="modal__field"><strong>Barbero:</strong> {{ transaccion.barbero }}</div>
          <div class="modal__field"><strong>Cliente:</strong> {{ transaccion.cliente || 'Consumidor Final' }}</div>
          <div class="modal__field" v-if="transaccion.contacto"><strong>Contacto:</strong> {{ transaccion.contacto }}</div>
          <div class="modal__field"><strong>Total:</strong> <span class="modal__total">Bs. {{ parseFloat(transaccion.total || 0).toFixed(2) }}</span></div>
        </div>
      </div>
      <div class="modal__actions">
        <button class="btn-secondary" @click="$emit('close')">Cancelar</button>
        <button class="btn-primary" @click="generarNota" id="btn-generar-nota">🖨️ Generar e Imprimir</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { pdfGenerator } from '../../utils/pdfGenerator'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  transaccion: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['close'])

function generarNota() {
  const blobUrl = pdfGenerator.exportarNotaVenta(props.transaccion, true)
  window.open(blobUrl, '_blank')
  emit('close')
}
</script>

<style scoped>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(13, 27, 42, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { width: 90%; max-width: 480px; padding: 2rem; }
.modal__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.modal__title { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; }
.modal__close { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--color-text-muted); }
.modal__desc { font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1rem; }
.modal__preview { background: var(--color-bg-primary); border-radius: var(--radius-md); padding: 1rem; }
.modal__field { font-size: 0.875rem; margin-bottom: 0.5rem; }
.modal__total { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; color: var(--color-success); }
.modal__actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }
</style>
