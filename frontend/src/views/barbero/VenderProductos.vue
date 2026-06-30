<template>
  <div class="venta-overlay" @click.self="cerrar">
    <div class="venta-panel">

      <div class="venta-header">
        <div>
          <h3 class="venta-titulo">Vender productos</h3>
          <p class="venta-sub">{{ nombreCliente }}</p>
        </div>
        <button class="btn-cerrar" @click="cerrar">✕</button>
      </div>

      <p v-if="store.error" class="aviso-error">{{ store.error }}</p>

      <!-- Ya vendido en esta cita -->
      <div v-if="store.detalleVenta.length > 0" class="bloque-confirmado">
        <p class="bloque-titulo">Ya agregado a esta venta</p>
        <div v-for="d in store.detalleVenta" :key="d.id_producto" class="fila-confirmada">
          <span>{{ d.nombre }} × {{ d.cantidad }}</span>
          <strong>{{ d.subtotal.toFixed(0) }} Bs.</strong>
        </div>
        <div class="fila-total">
          <span>Total vendido</span>
          <strong>{{ store.montoTotalVenta.toFixed(0) }} Bs.</strong>
        </div>
      </div>

      <!-- Catálogo -->
      <p class="bloque-titulo" style="margin-top: 1.25rem">Catálogo disponible</p>
      <div v-if="store.cargandoProductos" class="loading-msg">Cargando productos…</div>
      <div v-else class="catalogo-lista">
        <div v-for="p in store.productos" :key="p.id_producto" class="producto-item">
          <div class="producto-info">
            <span class="producto-nombre">{{ p.nombre }}</span>
            <span class="producto-precio">{{ p.precio_venta.toFixed(0) }} Bs.</span>
          </div>
          <span :class="['stock-tag', claseStock(p.stock)]">
            {{ p.stock === 0 ? 'Sin stock' : `${p.stock} disp.` }}
          </span>
          <button class="btn-agregar" :disabled="p.stock === 0" @click="store.agregarAlCarrito(p)">
            + Agregar
          </button>
        </div>
      </div>

      <!-- Carrito en curso -->
      <div v-if="store.carrito.length > 0" class="carrito-bloque">
        <p class="bloque-titulo">Por confirmar</p>
        <div v-for="c in store.carrito" :key="c.id_producto" class="fila-carrito">
          <span class="carrito-nombre">{{ c.nombre }}</span>
          <div class="carrito-cantidad">
            <button @click="store.cambiarCantidad(c.id_producto, c.cantidad - 1)" :disabled="c.cantidad <= 1">−</button>
            <span>{{ c.cantidad }}</span>
            <button @click="store.cambiarCantidad(c.id_producto, c.cantidad + 1)" :disabled="c.cantidad >= c.stock">+</button>
          </div>
          <strong>{{ (c.precio_venta * c.cantidad).toFixed(0) }} Bs.</strong>
          <button class="btn-quitar" @click="store.quitarDelCarrito(c.id_producto)">✕</button>
        </div>
        <div class="fila-total">
          <span>Subtotal a confirmar</span>
          <strong>{{ store.montoCarrito.toFixed(0) }} Bs.</strong>
        </div>
      </div>

      <div class="venta-footer">
        <button class="btn-secundario" @click="cerrar">Cerrar</button>
        <button
          class="btn-primario"
          :disabled="store.carrito.length === 0 || store.guardando"
          @click="confirmar">
          {{ store.guardando ? 'Guardando…' : 'Confirmar venta' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useVentaStore } from '../../stores/venta.js'

const props = defineProps({
  idReserva: { type: Number, required: true },
  nombreCliente: { type: String, default: '' },
})
const emit = defineEmits(['cerrar'])

const store = useVentaStore()

onMounted(() => {
  store.abrirVentaParaCita(props.idReserva)
})
onUnmounted(() => {
  store.cerrarVenta()
})

function claseStock(stock) {
  if (stock === 0) return 'sin-stock'
  if (stock <= 5) return 'stock-bajo'
  return 'stock-ok'
}

async function confirmar() {
  const ok = await store.confirmarVenta()
  if (ok) {
    // Se queda abierto para seguir agregando si quiere, o el barbero cierra manualmente.
  }
}

function cerrar() {
  emit('cerrar')
}
</script>

<style scoped>
.venta-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.6);
  display: flex; align-items: center; justify-content: center;
  padding: 1rem; z-index: 50;
}
.venta-panel {
  background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px;
  max-width: 560px; width: 100%; max-height: 88vh; overflow-y: auto;
  padding: 1.5rem; color: var(--color-text-primary);
}
.venta-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; }
.venta-titulo { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 700; margin: 0; }
.venta-sub { font-size: 0.8rem; color: var(--color-text-secondary); margin: 0.2rem 0 0; }
.btn-cerrar { background: transparent; border: none; color: var(--color-text-secondary); font-size: 1.1rem; cursor: pointer; }

.aviso-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.82rem; margin-bottom: 1rem; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 1.5rem 0; }

.bloque-titulo { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--color-text-secondary); margin: 0 0 0.6rem; }
.bloque-confirmado { background: rgba(22,163,74,0.08); border: 1px solid rgba(22,163,74,0.25); border-radius: 8px; padding: 0.85rem 1rem; }
.fila-confirmada { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.2rem 0; }

.catalogo-lista { display: flex; flex-direction: column; gap: 0.5rem; max-height: 220px; overflow-y: auto; margin-bottom: 0.5rem; }
.producto-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px; }
.producto-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.producto-nombre { font-size: 0.85rem; font-weight: 600; }
.producto-precio { font-size: 0.75rem; color: var(--color-gold); font-weight: 700; }

.stock-tag { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px; white-space: nowrap; }
.stock-tag.stock-ok { background: rgba(22,163,74,0.15); color: #4ade80; }
.stock-tag.stock-bajo { background: rgba(234,179,8,0.15); color: #facc15; }
.stock-tag.sin-stock { background: rgba(239,68,68,0.15); color: #f87171; }

.btn-agregar { font-size: 0.72rem; font-weight: 700; padding: 0.4rem 0.7rem; border-radius: 6px; border: 1px solid var(--color-gold); color: var(--color-gold); background: transparent; cursor: pointer; white-space: nowrap; }
.btn-agregar:disabled { opacity: 0.35; cursor: not-allowed; }
.btn-agregar:hover:not(:disabled) { background: rgba(201,168,76,0.1); }

.carrito-bloque { margin-top: 1.25rem; border-top: 1px solid var(--color-border); padding-top: 1rem; }
.fila-carrito { display: flex; align-items: center; gap: 0.75rem; padding: 0.4rem 0; }
.carrito-nombre { flex: 1; font-size: 0.85rem; }
.carrito-cantidad { display: flex; align-items: center; gap: 0.5rem; }
.carrito-cantidad button { width: 22px; height: 22px; border-radius: 4px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-primary); cursor: pointer; }
.carrito-cantidad button:disabled { opacity: 0.35; cursor: not-allowed; }
.btn-quitar { background: transparent; border: none; color: #f87171; cursor: pointer; font-size: 0.85rem; }

.fila-total { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; padding-top: 0.5rem; margin-top: 0.4rem; border-top: 1px dashed var(--color-border); }

.venta-footer { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border); }
.btn-primario { padding: 0.65rem 1.4rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; }
.btn-primario:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-secundario { padding: 0.65rem 1.1rem; background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); border-radius: 8px; font-size: 0.82rem; cursor: pointer; }
</style>