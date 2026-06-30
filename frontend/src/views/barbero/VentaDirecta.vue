<template>
  <div class="vd-wrapper">
    <div class="vd-header">
      <button class="btn-volver" @click="$emit('cerrar')">← Volver</button>
      <h2 class="vd-titulo">Venta a cliente sin cita</h2>
    </div>

    <div v-if="!notaLista" class="vd-formulario">
      <p class="subseccion-label">Datos del cliente</p>
      <div class="ci-row">
        <input v-model="ci" placeholder="CI" maxlength="10" class="campo-input" @blur="buscarPorCI" />
        <span v-if="buscando" class="aviso-gris">Buscando…</span>
      </div>
      <div class="form-grid">
        <input v-model="nombre1" placeholder="Nombre" class="campo-input" />
        <input v-model="apellido1" placeholder="Apellido" class="campo-input" />
        <input v-model="telefono" placeholder="Teléfono (opcional)" class="campo-input" />
        <input v-model="correo" placeholder="Correo (opcional)" type="email" class="campo-input" />
      </div>
      <p v-if="clienteEncontrado" class="aviso-verde">✓ Cliente ya registrado — datos autocompletados.</p>

      <p class="subseccion-label" style="margin-top:1.5rem">Productos</p>
      <div v-if="cargandoProductos" class="loading-msg">Cargando catálogo…</div>
      <div v-else class="catalogo-lista">
        <div v-for="p in productos" :key="p.id_producto" class="producto-item">
          <div class="producto-info">
            <span class="producto-nombre">{{ p.nombre }}</span>
            <span class="producto-precio">{{ p.precio_venta.toFixed(0) }} Bs.</span>
          </div>
          <span :class="['stock-tag', claseStock(p.stock)]">{{ p.stock === 0 ? 'Sin stock' : `${p.stock} disp.` }}</span>
          <button class="btn-agregar" :disabled="p.stock === 0" @click="agregar(p)">+ Agregar</button>
        </div>
      </div>

      <div v-if="carrito.length" class="carrito-bloque">
        <div v-for="c in carrito" :key="c.id_producto" class="fila-carrito">
          <span class="carrito-nombre">{{ c.nombre }}</span>
          <div class="carrito-cantidad">
            <button @click="cambiarCantidad(c, c.cantidad - 1)" :disabled="c.cantidad <= 1">−</button>
            <span>{{ c.cantidad }}</span>
            <button @click="cambiarCantidad(c, c.cantidad + 1)" :disabled="c.cantidad >= c.stock">+</button>
          </div>
          <strong>{{ (c.precio_venta * c.cantidad).toFixed(0) }} Bs.</strong>
          <button class="btn-quitar" @click="quitar(c)">✕</button>
        </div>
        <div class="fila-total"><span>Total</span><strong>{{ montoCarrito.toFixed(0) }} Bs.</strong></div>
      </div>

      <p class="subseccion-label" style="margin-top:1.5rem">Método de pago</p>
      <div class="pf-metodos">
        <button v-for="m in metodos" :key="m.value" :class="['metodo-btn', metodoPago === m.value ? 'activo' : '']" @click="metodoPago = m.value">
          {{ m.icon }} {{ m.label }}
        </button>
      </div>

      <div v-if="qr" class="qr-bloque">
        <p class="qr-titulo">Cliente debe escanear y pagar</p>
        <div class="qr-placeholder"><span>▦</span></div>
        <p class="qr-ref">{{ qr.referencia }}</p>
        <p class="qr-monto">{{ qr.monto.toFixed(2) }} {{ qr.moneda }}</p>
      </div>

      <p v-if="error" class="aviso-error">{{ error }}</p>

      <div class="vd-footer">
        <button v-if="!qr" class="btn-primario" :disabled="!puedeContinuar || procesando" @click="iniciar">
          {{ procesando ? 'Procesando…' : (metodoPago === 'QR' ? 'Generar QR' : 'Confirmar venta') }}
        </button>
        <button v-else class="btn-primario" :disabled="procesando" @click="confirmarQR">
          {{ procesando ? 'Confirmando…' : '✓ Ya pagó — Confirmar' }}
        </button>
      </div>
    </div>

    <NotaVentaModal v-else :nota="nota" @cerrar="reiniciar" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../services/api.js'
import { ventaDirectaService } from '../../services/ventaDirectaService.js'
import NotaVentaModal from '../../components/barbero/NotaVentaModal.vue'

const ci = ref(''), nombre1 = ref(''), apellido1 = ref(''), telefono = ref(''), correo = ref('')
const buscando = ref(false)
const clienteEncontrado = ref(false)

const productos = ref([])
const cargandoProductos = ref(true)
const carrito = ref([])

const metodoPago = ref('Efectivo')
const metodos = [
  { value: 'Efectivo', label: 'Efectivo', icon: '💵' },
  { value: 'QR', label: 'QR', icon: '📱' },
]

const qr = ref(null)
const procesando = ref(false)
const error = ref(null)
const notaLista = ref(false)
const nota = ref(null)

const montoCarrito = computed(() => carrito.value.reduce((acc, p) => acc + p.precio_venta * p.cantidad, 0))
const puedeContinuar = computed(() => ci.value && nombre1.value && apellido1.value && carrito.value.length > 0)

cargarProductos()
async function cargarProductos() {
  cargandoProductos.value = true
  try {
    const { data } = await api.get('/barbero/productos')
    productos.value = data.productos
  } finally {
    cargandoProductos.value = false
  }
}

async function buscarPorCI() {
  if (!ci.value) return
  buscando.value = true
  try {
    const { data } = await api.get(`/barbero/cliente/${ci.value}`)
    clienteEncontrado.value = data.encontrado
    if (data.encontrado && data.cliente) {
      nombre1.value = data.cliente.Nombre1 || ''
      apellido1.value = data.cliente.Apellido1 || ''
      telefono.value = data.cliente.Telefono || ''
      correo.value = data.cliente.Correo || ''
    }
  } catch {
    // CI nuevo, sin problema
  } finally {
    buscando.value = false
  }
}

function claseStock(stock) {
  if (stock === 0) return 'sin-stock'
  if (stock <= 5) return 'stock-bajo'
  return 'stock-ok'
}

function agregar(producto) {
  const existente = carrito.value.find((p) => p.id_producto === producto.id_producto)
  if (existente) {
    if (existente.cantidad < producto.stock) existente.cantidad++
  } else if (producto.stock > 0) {
    carrito.value.push({ ...producto, cantidad: 1 })
  }
}
function cambiarCantidad(item, cantidad) {
  item.cantidad = Math.max(1, Math.min(cantidad, item.stock))
}
function quitar(item) {
  carrito.value = carrito.value.filter((p) => p.id_producto !== item.id_producto)
}

function armarPayload() {
  return {
    ci: ci.value,
    nombre1: nombre1.value,
    apellido1: apellido1.value,
    telefono: telefono.value || null,
    correo: correo.value || null,
    productos: carrito.value.map((p) => ({ id_producto: p.id_producto, cantidad: p.cantidad })),
    metodo_pago: metodoPago.value,
  }
}

async function iniciar() {
  procesando.value = true
  error.value = null
  try {
    const { data } = await ventaDirectaService.iniciar(armarPayload())
    if (data.pendiente) {
      qr.value = data.qr
    } else {
      nota.value = data.nota
      notaLista.value = true
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'No se pudo registrar la venta.'
  } finally {
    procesando.value = false
  }
}

async function confirmarQR() {
  procesando.value = true
  error.value = null
  try {
    const { data } = await ventaDirectaService.confirmar(armarPayload())
    nota.value = data.nota
    notaLista.value = true
  } catch (err) {
    error.value = err.response?.data?.error || 'No se pudo confirmar el pago.'
  } finally {
    procesando.value = false
  }
}

function reiniciar() {
  ci.value = ''; nombre1.value = ''; apellido1.value = ''; telefono.value = ''; correo.value = ''
  clienteEncontrado.value = false
  carrito.value = []
  qr.value = null
  notaLista.value = false
  nota.value = null
  metodoPago.value = 'Efectivo'
  cargarProductos()
}
</script>

<style scoped>
/* Reutiliza clases visuales ya establecidas en VenderProductos.vue / Crearcitapresencial.vue */
.vd-wrapper { max-width: 640px; margin: 0 auto; padding: 0 0 2rem; color: var(--color-text-primary); }
.vd-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.btn-volver { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; }
.vd-titulo { font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; margin: 0; }
.subseccion-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--color-text-secondary); margin: 0 0 0.75rem; }
.ci-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.85rem; }
.campo-input { background: var(--color-bg-input, rgba(255,255,255,0.05)); border: 1px solid var(--color-border); border-radius: 7px; padding: 0.6rem 0.85rem; font-size: 0.875rem; color: var(--color-text-primary); width: 100%; box-sizing: border-box; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
@media (max-width: 520px) { .form-grid { grid-template-columns: 1fr; } }
.aviso-verde { font-size: 0.78rem; color: #4ade80; margin-top: 0.4rem; }
.aviso-gris { font-size: 0.75rem; color: var(--color-text-secondary); }
.aviso-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.82rem; margin: 1rem 0; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 1rem 0; }
.catalogo-lista { display: flex; flex-direction: column; gap: 0.5rem; max-height: 260px; overflow-y: auto; }
.producto-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px; }
.producto-info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.producto-nombre { font-size: 0.85rem; font-weight: 600; }
.producto-precio { font-size: 0.75rem; color: var(--color-gold); font-weight: 700; }
.stock-tag { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px; white-space: nowrap; }
.stock-tag.stock-ok { background: rgba(22,163,74,0.15); color: #4ade80; }
.stock-tag.stock-bajo { background: rgba(234,179,8,0.15); color: #facc15; }
.stock-tag.sin-stock { background: rgba(239,68,68,0.15); color: #f87171; }
.btn-agregar { font-size: 0.72rem; font-weight: 700; padding: 0.4rem 0.7rem; border-radius: 6px; border: 1px solid var(--color-gold); color: var(--color-gold); background: transparent; cursor: pointer; }
.btn-agregar:disabled { opacity: 0.35; cursor: not-allowed; }
.carrito-bloque { margin-top: 1rem; border-top: 1px solid var(--color-border); padding-top: 1rem; }
.fila-carrito { display: flex; align-items: center; gap: 0.75rem; padding: 0.4rem 0; }
.carrito-nombre { flex: 1; font-size: 0.85rem; }
.carrito-cantidad { display: flex; align-items: center; gap: 0.5rem; }
.carrito-cantidad button { width: 22px; height: 22px; border-radius: 4px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-primary); cursor: pointer; }
.btn-quitar { background: transparent; border: none; color: #f87171; cursor: pointer; }
.fila-total { display: flex; justify-content: space-between; font-weight: 700; padding-top: 0.5rem; border-top: 1px dashed var(--color-border); }
.pf-metodos { display: flex; gap: 0.6rem; margin-bottom: 1rem; }
.metodo-btn { flex: 1; padding: 0.65rem; border: 2px solid var(--color-border); border-radius: 8px; background: transparent; color: var(--color-text-secondary); cursor: pointer; font-size: 0.8rem; font-weight: 600; }
.metodo-btn.activo { border-color: var(--color-gold); background: rgba(201,168,76,0.12); color: var(--color-gold); }
.qr-bloque { text-align: center; margin-bottom: 1.25rem; }
.qr-placeholder { width: 140px; height: 140px; margin: 0.5rem auto; border: 2px dashed var(--color-border); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 3rem; background: #fff; color: #1a1a2e; }
.vd-footer { display: flex; justify-content: flex-end; margin-top: 1.25rem; }
.btn-primario { padding: 0.7rem 1.5rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.btn-primario:disabled { opacity: 0.45; cursor: not-allowed; }
</style>