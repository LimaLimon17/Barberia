import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { ventaService } from '../services/ventaService.js'

export const useVentaStore = defineStore('venta', () => {
  const idReservaActiva = ref(null)
  const productos = ref([])
  const cargandoProductos = ref(false)

  const carrito = ref([]) // [{ id_producto, nombre, precio_venta, stock, cantidad }]
  const detalleVenta = ref([]) // lo ya confirmado en backend
  const montoTotalVenta = ref(0)

  const cargandoVenta = ref(false)
  const guardando = ref(false)
  const error = ref(null)

  const montoCarrito = computed(() =>
    carrito.value.reduce((acc, p) => acc + p.precio_venta * p.cantidad, 0)
  )

  async function abrirVentaParaCita(idReserva) {
    idReservaActiva.value = idReserva
    carrito.value = []
    error.value = null
    await Promise.all([cargarProductos(), cargarVentaExistente(idReserva)])
  }

  async function cargarProductos() {
    cargandoProductos.value = true
    try {
      const { data } = await ventaService.productosDisponibles()
      productos.value = data.productos
    } catch {
      error.value = 'No se pudo cargar el catálogo de productos.'
    } finally {
      cargandoProductos.value = false
    }
  }

  async function cargarVentaExistente(idReserva) {
    cargandoVenta.value = true
    try {
      const { data } = await ventaService.ventaDeLaCita(idReserva)
      detalleVenta.value = data.detalle
      montoTotalVenta.value = data.monto_total
    } catch {
      error.value = 'No se pudo cargar la venta de esta cita.'
    } finally {
      cargandoVenta.value = false
    }
  }

  function agregarAlCarrito(producto) {
    const existente = carrito.value.find((p) => p.id_producto === producto.id_producto)
    if (existente) {
      if (existente.cantidad < producto.stock) existente.cantidad++
    } else {
      if (producto.stock > 0) {
        carrito.value.push({ ...producto, cantidad: 1 })
      }
    }
  }

  function cambiarCantidad(idProducto, cantidad) {
    const item = carrito.value.find((p) => p.id_producto === idProducto)
    if (!item) return
    const max = item.stock
    item.cantidad = Math.max(1, Math.min(cantidad, max))
  }

  function quitarDelCarrito(idProducto) {
    carrito.value = carrito.value.filter((p) => p.id_producto !== idProducto)
  }

  async function confirmarVenta() {
    if (carrito.value.length === 0) return false
    guardando.value = true
    error.value = null
    try {
      const payload = carrito.value.map((p) => ({ id_producto: p.id_producto, cantidad: p.cantidad }))
      const { data } = await ventaService.agregarProductos(idReservaActiva.value, payload)
      detalleVenta.value = data.detalle
      montoTotalVenta.value = data.venta.monto_total
      carrito.value = []
      await cargarProductos() // refresca stock mostrado
      return true
    } catch (err) {
      error.value = err.response?.data?.error || 'No se pudo registrar la venta de productos.'
      return false
    } finally {
      guardando.value = false
    }
  }

  function cerrarVenta() {
    idReservaActiva.value = null
    carrito.value = []
    detalleVenta.value = []
    montoTotalVenta.value = 0
    error.value = null
  }

  return {
    idReservaActiva, productos, cargandoProductos,
    carrito, detalleVenta, montoTotalVenta, montoCarrito,
    cargandoVenta, guardando, error,
    abrirVentaParaCita, agregarAlCarrito, cambiarCantidad, quitarDelCarrito,
    confirmarVenta, cerrarVenta,
  }
})