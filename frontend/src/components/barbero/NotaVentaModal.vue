<template>
  <div class="nota-overlay" @click.self="$emit('cerrar')">
    <div class="nota-panel">

      <div class="nota-exito">
        <div class="exito-icono">✓</div>
        <h3 class="exito-titulo">Pago confirmado</h3>
        <p class="exito-sub">Nota de Venta #{{ nota.numero }}</p>
      </div>

      <div class="nota-resumen">
        <div v-if="nota.servicios.length" class="resumen-bloque">
          <p class="resumen-titulo">Servicios</p>
          <div v-for="(s, i) in nota.servicios" :key="'s'+i" class="resumen-fila">
            <span>{{ s.nombre }}</span><span>{{ s.precio.toFixed(0) }} Bs.</span>
          </div>
        </div>

        <div v-if="nota.productos.length" class="resumen-bloque">
          <p class="resumen-titulo">Productos</p>
          <div v-for="(p, i) in nota.productos" :key="'p'+i" class="resumen-fila">
            <span>{{ p.nombre }} × {{ p.cantidad }}</span><span>{{ p.subtotal.toFixed(0) }} Bs.</span>
          </div>
        </div>

        <div v-if="nota.anticipo_ya_pagado > 0" class="resumen-fila secundaria">
          <span>Anticipo ya pagado</span><span>{{ nota.anticipo_ya_pagado.toFixed(0) }} Bs.</span>
        </div>

        <div class="resumen-total">
          <span>TOTAL COBRADO AHORA</span>
          <strong>{{ nota.total_pagado_ahora.toFixed(2) }} Bs.</strong>
        </div>
        <p class="metodo-pago">Método: {{ nota.metodo_pago }}</p>
      </div>

      <div class="nota-acciones">
        <button class="btn-primario" @click="descargarPDF">⬇ Descargar / Imprimir PDF</button>
        <button class="btn-secundario" @click="$emit('cerrar')">Cerrar</button>
      </div>

      <!-- Layout oculto para impresión, igual al patrón usado en PasoPago.vue -->
      <div style="display:none;">
        <div ref="ticketRef" class="ticket-pdf-layout">
          <div class="ticket-header">
            <h1 class="ticket-brand">THE LAMPLIGHT</h1>
            <p class="ticket-tagline">Barber Shop · La Paz</p>
            <div class="ticket-divider"></div>
            <h2 class="ticket-title">NOTA DE VENTA</h2>
          </div>

          <div class="ticket-body">
            <div class="ticket-meta">
              <p><strong>Nro:</strong> {{ nota.numero }}</p>
              <p><strong>Fecha:</strong> {{ nota.fecha }}</p>
            </div>

            <div class="ticket-table">
              <div class="ticket-row"><span>Barbero:</span><strong>{{ nota.barbero }}</strong></div>
              <div class="ticket-row"><span>Cliente:</span><strong>{{ nota.cliente.nombre }}</strong></div>
              <div class="ticket-row"><span>Contacto:</span><strong>{{ nota.cliente.telefono || nota.cliente.correo || '—' }}</strong></div>
            </div>

            <div v-if="nota.servicios.length" class="ticket-table">
              <div class="ticket-row" style="font-weight:700;"><span>SERVICIOS</span><span></span></div>
              <div v-for="(s, i) in nota.servicios" :key="'ps'+i" class="ticket-row">
                <span>{{ s.nombre }} ({{ s.duracion }} min)</span><strong>{{ s.precio.toFixed(0) }} Bs.</strong>
              </div>
            </div>

            <div v-if="nota.productos.length" class="ticket-table">
              <div class="ticket-row" style="font-weight:700;"><span>PRODUCTOS</span><span></span></div>
              <div v-for="(p, i) in nota.productos" :key="'pp'+i" class="ticket-row">
                <span>{{ p.nombre }} × {{ p.cantidad }}</span><strong>{{ p.subtotal.toFixed(0) }} Bs.</strong>
              </div>
            </div>

            <div class="ticket-totals">
              <div v-if="nota.anticipo_ya_pagado > 0" class="ticket-row-total">
                <span>Anticipo ya pagado:</span><strong>{{ nota.anticipo_ya_pagado.toFixed(0) }} Bs.</strong>
              </div>
              <div class="ticket-row-total destacado">
                <span>Total cobrado ({{ nota.metodo_pago }}):</span><strong>{{ nota.total_pagado_ahora.toFixed(2) }} Bs.</strong>
              </div>
            </div>
          </div>

          <div class="ticket-footer">
            <p>¡Gracias por tu confianza!</p>
            <small>Documento sin valor fiscal.</small>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  nota: { type: Object, required: true },
})
defineEmits(['cerrar'])

const ticketRef = ref(null)

async function descargarPDF() {
  const html2pdf = (await import('html2pdf.js')).default
  const clonNode = ticketRef.value.cloneNode(true)
  clonNode.style.display = 'block'

  const opciones = {
    margin: [0.5, 0.5, 0.5, 0.5],
    filename: `Nota-Venta-${props.nota.numero}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, logging: false },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
  }

  html2pdf().set(opciones).from(clonNode).save()
}
</script>

<style scoped>
.nota-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 60; }
.nota-panel { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px; max-width: 480px; width: 100%; max-height: 88vh; overflow-y: auto; padding: 1.5rem; color: var(--color-text-primary); text-align: center; }
.nota-exito { margin-bottom: 1.25rem; }
.exito-icono { width: 56px; height: 56px; border-radius: 50%; background: rgba(22,163,74,0.15); border: 2px solid #16a34a; color: #4ade80; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; }
.exito-titulo { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700; margin: 0 0 0.2rem; }
.exito-sub { font-size: 0.8rem; color: var(--color-text-secondary); margin: 0; }

.nota-resumen { text-align: left; border: 1px solid var(--color-border); border-radius: 8px; overflow: hidden; margin-bottom: 1.25rem; }
.resumen-bloque { padding: 0.85rem 1rem; border-bottom: 1px solid var(--color-border); }
.resumen-titulo { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-secondary); font-weight: 700; margin: 0 0 0.4rem; }
.resumen-fila { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.15rem 0; }
.resumen-fila.secundaria { padding: 0.6rem 1rem; color: var(--color-text-secondary); font-size: 0.8rem; }
.resumen-total { display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; background: rgba(201,168,76,0.08); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; }
.resumen-total strong { font-family: var(--font-heading); font-size: 1.2rem; color: var(--color-gold); }
.metodo-pago { font-size: 0.75rem; color: var(--color-text-secondary); padding: 0.4rem 1rem 0.7rem; margin: 0; }

.nota-acciones { display: flex; flex-direction: column; gap: 0.6rem; }
.btn-primario { padding: 0.7rem 1.2rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.btn-secundario { padding: 0.6rem 1.2rem; background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); border-radius: 8px; font-size: 0.82rem; cursor: pointer; }

.ticket-pdf-layout { padding: 40px; background: #fdfcf9; color: #0d1f2d; font-family: 'Montserrat', sans-serif; }
.ticket-header { text-align: center; margin-bottom: 30px; }
.ticket-brand { font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 800; letter-spacing: 2px; margin: 0; }
.ticket-tagline { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 3px; color: #9a8466; margin: 5px 0 0; }
.ticket-divider { height: 2px; background: #9a8466; width: 60px; margin: 15px auto; }
.ticket-title { font-size: 1.05rem; letter-spacing: 2px; font-weight: 700; margin: 0; }
.ticket-meta { display: flex; justify-content: space-between; font-size: 0.85rem; border-bottom: 1px solid #e5dfd3; padding-bottom: 10px; margin-bottom: 20px; }
.ticket-table { border: 1px solid #e5dfd3; background: #fff; margin-bottom: 16px; }
.ticket-row, .ticket-row-total { display: flex; justify-content: space-between; padding: 10px 14px; font-size: 0.88rem; border-bottom: 1px solid #e5dfd3; }
.ticket-row:last-child, .ticket-row-total:last-child { border-bottom: none; }
.ticket-totals { border: 1px solid #e5dfd3; background: #fff; }
.ticket-row-total.destacado { background: #f4ece2; font-weight: 700; }
.ticket-footer { text-align: center; margin-top: 30px; border-top: 1px dashed #9a8466; padding-top: 16px; }
.ticket-footer p { font-family: 'Cinzel', serif; font-weight: 700; margin-bottom: 4px; }
.ticket-footer small { display: block; font-size: 0.7rem; color: #777; }
</style>