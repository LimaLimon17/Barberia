<template>
  <div class="pf-overlay" @click.self="$emit('cerrar')">
    <div class="pf-panel" v-if="!notaLista">
      <h3 class="pf-titulo">Cobrar saldo final</h3>

      <div v-if="cargando" class="loading-msg">Calculando totales…</div>
      <template v-else-if="resumen">
        <div class="pf-resumen">
          <div class="pf-fila"><span>Saldo del servicio (50%)</span><strong>{{ resumen.saldo_servicio.toFixed(0) }} Bs.</strong></div>
          <div class="pf-fila"><span>Productos vendidos</span><strong>{{ resumen.subtotal_productos.toFixed(0) }} Bs.</strong></div>
          <div class="pf-fila total"><span>TOTAL A COBRAR</span><strong>{{ resumen.total.toFixed(2) }} Bs.</strong></div>
        </div>

        <p v-if="error" class="aviso-error">{{ error }}</p>

        <div v-if="!qr" class="pf-metodos">
          <button
            v-for="m in metodos" :key="m.value"
            :class="['metodo-btn', metodoSeleccionado === m.value ? 'activo' : '']"
            @click="metodoSeleccionado = m.value">
            {{ m.icon }} {{ m.label }}
          </button>
        </div>

        <div v-else class="qr-bloque">
          <p class="qr-titulo">Cliente debe escanear y pagar</p>
          <div class="qr-placeholder"><span>▦</span></div>
          <p class="qr-ref">{{ qr.referencia }}</p>
          <p class="qr-monto">{{ qr.monto.toFixed(2) }} {{ qr.moneda }}</p>
        </div>

        <div class="pf-acciones">
          <button class="btn-secundario" @click="$emit('cerrar')" :disabled="procesando">Cancelar</button>
          <button v-if="!qr" class="btn-primario" :disabled="procesando" @click="iniciar">
            {{ procesando ? 'Procesando…' : (metodoSeleccionado === 'QR' ? 'Generar QR' : 'Confirmar pago') }}
          </button>
          <button v-else class="btn-primario" :disabled="procesando" @click="confirmarQR">
            {{ procesando ? 'Confirmando…' : '✓ Ya pagó — Confirmar' }}
          </button>
        </div>
      </template>
    </div>

    <NotaVentaModal v-else :nota="nota" @cerrar="$emit('completado')" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { pagoFinalService } from '../../services/pagoFinalService.js'
import NotaVentaModal from './NotaVentaModal.vue'

const props = defineProps({ idReserva: { type: Number, required: true } })
defineEmits(['cerrar', 'completado'])

const cargando = ref(true)
const resumen = ref(null)
const error = ref(null)
const metodoSeleccionado = ref('Efectivo')
const qr = ref(null)
const procesando = ref(false)
const notaLista = ref(false)
const nota = ref(null)

const metodos = [
  { value: 'Efectivo', label: 'Efectivo', icon: '💵' },
  { value: 'QR', label: 'QR', icon: '📱' },
]

onMounted(cargarResumen)

async function cargarResumen() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await pagoFinalService.resumen(props.idReserva)
    if (data.ya_pagado) {
      error.value = 'El saldo de esta cita ya fue pagado anteriormente.'
    }
    resumen.value = data
  } catch {
    error.value = 'No se pudo cargar el resumen de pago.'
  } finally {
    cargando.value = false
  }
}

async function iniciar() {
  procesando.value = true
  error.value = null
  try {
    const { data } = await pagoFinalService.iniciar(props.idReserva, metodoSeleccionado.value)
    if (data.pendiente) {
      qr.value = data.qr
    } else {
      nota.value = data.nota
      notaLista.value = true
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'No se pudo procesar el pago.'
  } finally {
    procesando.value = false
  }
}

async function confirmarQR() {
  procesando.value = true
  error.value = null
  try {
    const { data } = await pagoFinalService.confirmar(props.idReserva)
    nota.value = data.nota
    notaLista.value = true
  } catch (err) {
    error.value = err.response?.data?.error || 'No se pudo confirmar el pago.'
  } finally {
    procesando.value = false
  }
}
</script>

<style scoped>
.pf-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 55; }
.pf-panel { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px; max-width: 420px; width: 100%; padding: 1.5rem; color: var(--color-text-primary); }
.pf-titulo { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 700; margin: 0 0 1.1rem; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 1.5rem 0; }
.pf-resumen { border: 1px solid var(--color-border); border-radius: 8px; overflow: hidden; margin-bottom: 1.1rem; }
.pf-fila { display: flex; justify-content: space-between; padding: 0.6rem 0.9rem; font-size: 0.85rem; border-bottom: 1px solid var(--color-border); }
.pf-fila:last-child { border-bottom: none; }
.pf-fila.total { background: rgba(201,168,76,0.08); font-weight: 700; }
.pf-fila.total strong { color: var(--color-gold); font-size: 1.1rem; }
.aviso-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.8rem; margin-bottom: 1rem; }
.pf-metodos { display: flex; gap: 0.6rem; margin-bottom: 1.25rem; }
.metodo-btn { flex: 1; padding: 0.7rem; border: 2px solid var(--color-border); border-radius: 8px; background: transparent; color: var(--color-text-secondary); cursor: pointer; font-size: 0.82rem; font-weight: 600; }
.metodo-btn.activo { border-color: var(--color-gold); background: rgba(201,168,76,0.12); color: var(--color-gold); }
.qr-bloque { text-align: center; margin-bottom: 1.25rem; }
.qr-titulo { font-size: 0.82rem; margin-bottom: 0.85rem; }
.qr-placeholder { width: 140px; height: 140px; margin: 0 auto 0.5rem; border: 2px dashed var(--color-border); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 3rem; background: #fff; color: #1a1a2e; }
.qr-ref { font-size: 0.7rem; color: var(--color-text-secondary); font-family: monospace; }
.qr-monto { font-weight: 700; color: var(--color-gold); }
.pf-acciones { display: flex; justify-content: flex-end; gap: 0.6rem; }
.btn-primario { padding: 0.65rem 1.3rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; }
.btn-primario:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secundario { padding: 0.65rem 1.1rem; background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); border-radius: 8px; cursor: pointer; }
</style>