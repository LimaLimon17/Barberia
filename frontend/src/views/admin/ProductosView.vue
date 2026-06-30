<template>
  <section class="page">
    <header class="page-header">
      <div>
        <h1>Inventario de Productos</h1>
        <p>Registra productos, controla el stock y revisa alertas de bajo inventario.</p>
      </div>
    </header>

    <AlertMessage :mensaje="message" :tipo="messageType" />
    <LoadingSpinner :show="loading" />

    <div class="card">
      <div class="section-title">
        <h2>{{ form.IdProducto ? 'Editar producto' : 'Registrar nuevo producto' }}</h2>
        <p>El precio de venta y la comisión inicial se guardan como el primer período de historial.</p>
      </div>

      <form class="grid" @submit.prevent="guardarProducto">
        <label class="field">
          <span>Nombre del producto</span>
          <input v-model="form.Nombre" type="text" placeholder="Ej: Gel fijador 250ml" required />
          <small>Nombre único que verá el barbero al vender.</small>
        </label>

        <label class="field">
          <span>Costo de compra (Bs.)</span>
          <input v-model.number="form.CostoCompra" type="number" min="0" step="0.01" placeholder="Ej: 25.00" required />
          <small>Lo que cuesta adquirir una unidad.</small>
        </label>

        <label class="field">
          <span>Porcentaje de venta</span>
          <input v-model.number="form.PorcentajeVenta" type="number" min="10" step="0.01" placeholder="Ej: 20" required />
          <small>Margen sobre el costo de compra. Mínimo 10%.</small>
        </label>

        <label class="field">
          <span>Porcentaje de comisión del barbero</span>
          <input v-model.number="form.PorcentajeBarbero" type="number" min="0" step="0.01" placeholder="Ej: 10" required />
          <small>No debe igualar ni superar el porcentaje de venta.</small>
        </label>

        <label class="field">
          <span>Precio de venta (Bs.)</span>
          <input v-model.number="form.PrecioVenta" type="number" min="0" step="0.01" placeholder="Calculado o manual" required />
          <small>Precio final que verá el barbero. Sugerido: {{ precioSugerido }}</small>
        </label>

        <label class="field">
          <span>Stock inicial</span>
          <input v-model.number="form.StockActual" type="number" min="0" placeholder="Ej: 30" required />
          <small>Unidades disponibles al momento de registrar el producto.</small>
        </label>

        <div class="field actions-field">
          <span>Acción</span>
          <div style="display:flex; gap:8px;">
            <button type="submit">{{ form.IdProducto ? 'Actualizar producto' : 'Crear producto' }}</button>
            <button type="button" class="secundario" v-if="form.IdProducto" @click="resetForm">Cancelar edición</button>
          </div>
          <small>{{ form.IdProducto ? 'Si cambias precio/porcentajes, se abre un nuevo período de historial.' : 'Se crea con historial inicial de porcentajes.' }}</small>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="section-title">
        <h2>Catálogo de productos</h2>
        <p>🟡 Stock ≤ 5 unidades · 🔴 Sin stock</p>
      </div>

      <div class="filtro-buscar">
        <input v-model="buscar" type="text" placeholder="Buscar producto por nombre..." @input="cargarProductos" />
      </div>

      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Costo compra</th>
            <th>Precio venta</th>
            <th>% Venta</th>
            <th>% Barbero</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in productos" :key="p.IdProducto" :class="estadoFila(p)">
            <td>{{ p.Nombre }}</td>
            <td>Bs {{ Number(p.CostoCompra).toFixed(2) }}</td>
            <td>Bs {{ Number(p.PrecioVenta).toFixed(2) }}</td>
            <td>{{ p.PorcentajeVenta }}%</td>
            <td>{{ p.PorcentajeBarbero }}%</td>
            <td>
              <span class="stock-badge" :class="claseStock(p.StockActual)">
                {{ p.StockActual }}
              </span>
            </td>
            <td>{{ p.EstadoA === 0 ? 'Inactivo' : 'Activo' }}</td>
            <td class="actions">
              <button @click="editarProducto(p)">Editar</button>
              <button @click="abrirLote(p)">+ Stock</button>
              <button
                v-if="p.EstadoA !== 0"
                class="danger"
                @click="desactivarProducto(p.IdProducto)"
              >Desactivar</button>
            </td>
          </tr>
        </tbody>
      </table>

      <p v-if="!loading && productos.length === 0" class="vacio">No hay productos registrados todavía.</p>
    </div>

    <!-- Modal registrar lote -->
    <div v-if="loteAbierto" class="modal-overlay" @click.self="cerrarLote">
      <div class="modal">
        <h3>Registrar entrada de stock — {{ loteAbierto.Nombre }}</h3>
        <form @submit.prevent="guardarLote">
          <label class="field">
            <span>Cantidad recibida</span>
            <input v-model.number="lote.CantidadRecibida" type="number" min="1" required />
          </label>
          <label class="field">
            <span>Costo unitario (Bs.)</span>
            <input v-model.number="lote.CostoUnitario" type="number" min="0" step="0.01" required />
          </label>
          <label class="field">
            <span>Fecha de ingreso</span>
            <input v-model="lote.FechaIngreso" type="date" />
          </label>
          <div class="modal-acciones">
            <button type="button" class="secundario" @click="cerrarLote">Cancelar</button>
            <button type="submit">Registrar entrada</button>
          </div>
        </form>
      </div>
    </div>

  </section>
</template>

<script setup>
import { onMounted, computed, reactive, ref } from "vue";
import AlertMessage from "../../components/common/AlertMessage.vue";
import LoadingSpinner from "../../components/common/LoadingSpinner.vue";
import { productosService } from "../../services/productosService";

const productos = ref([]);
const loading = ref(false);
const message = ref("");
const messageType = ref("info");
const buscar = ref("");

const form = reactive({
  IdProducto: null,
  Nombre: "",
  CostoCompra: 0,
  PrecioVenta: 0,
  PorcentajeVenta: 10,
  PorcentajeBarbero: 5,
  StockActual: 0,
});

const loteAbierto = ref(null);
const lote = reactive({
  CantidadRecibida: 1,
  CostoUnitario: 0,
  FechaIngreso: new Date().toISOString().slice(0, 10),
});

const precioSugerido = computed(() => {
  const costo = Number(form.CostoCompra) || 0;
  const porcentaje = Number(form.PorcentajeVenta) || 0;
  return `Bs ${(costo * (1 + porcentaje / 100)).toFixed(2)}`;
});

const notify = (text, type = "success") => {
  message.value = text;
  messageType.value = type;
};

function claseStock(stock) {
  if (stock === 0) return "stock-cero";
  if (stock <= 5) return "stock-bajo";
  return "stock-ok";
}

function estadoFila(p) {
  return p.EstadoA === 0 ? "fila-inactiva" : "";
}

async function cargarProductos() {
  loading.value = true;
  try {
    const { data } = await productosService.listar({ buscar: buscar.value, soloActivos: false });
    productos.value = data.data;
  } catch (error) {
    notify(error.friendlyMessage || "Error al cargar productos", "error");
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  Object.assign(form, {
    IdProducto: null,
    Nombre: "",
    CostoCompra: 0,
    PrecioVenta: 0,
    PorcentajeVenta: 10,
    PorcentajeBarbero: 5,
    StockActual: 0,
  });
}

function editarProducto(p) {
  Object.assign(form, {
    IdProducto: p.IdProducto,
    Nombre: p.Nombre,
    CostoCompra: Number(p.CostoCompra),
    PrecioVenta: Number(p.PrecioVenta),
    PorcentajeVenta: Number(p.PorcentajeVenta),
    PorcentajeBarbero: Number(p.PorcentajeBarbero),
    StockActual: p.StockActual,
  });
}

async function guardarProducto() {
  try {
    if (form.IdProducto) {
      await productosService.actualizar(form.IdProducto, form);
      notify("Producto actualizado");
    } else {
      await productosService.crear(form);
      notify("Producto registrado");
    }
    resetForm();
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || error.response?.data?.message || "No se pudo guardar el producto", "error");
  }
}

async function desactivarProducto(id) {
  if (!confirm("¿Desactivar este producto? Ya no estará disponible para venta.")) return;
  try {
    await productosService.desactivar(id);
    notify("Producto desactivado");
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo desactivar", "error");
  }
}

function abrirLote(producto) {
  loteAbierto.value = producto;
  Object.assign(lote, {
    CantidadRecibida: 1,
    CostoUnitario: Number(producto.CostoCompra),
    FechaIngreso: new Date().toISOString().slice(0, 10),
  });
}

function cerrarLote() {
  loteAbierto.value = null;
}

async function guardarLote() {
  try {
    await productosService.registrarLote(loteAbierto.value.IdProducto, lote);
    notify("Stock actualizado correctamente");
    cerrarLote();
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo registrar el lote", "error");
  }
}

onMounted(cargarProductos);
</script>

<style scoped>
.page { padding: 24px; }
.page-header h1 { margin: 0; }
.page-header p, .section-title p { margin: 6px 0 0; color: #667085; }
.card { background: #fff; border: 1px solid #e4e7ec; border-radius: 16px; padding: 18px; margin-top: 16px; box-shadow: 0 8px 24px rgba(16,24,40,.06); }
.section-title { margin-bottom: 14px; }
.section-title h2 { margin: 0; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; align-items: start; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field span { font-size: 14px; font-weight: 700; color: #344054; }
.field small { color: #667085; line-height: 1.35; }
input, select, button { border: 1px solid #d0d5dd; border-radius: 10px; padding: 10px 12px; }
input:focus, select:focus { outline: 2px solid #b2ddff; border-color: #53b1fd; }
button { cursor: pointer; font-weight: 700; background: #111827; color: #fff; }
button.secundario { background: #f9fafb; color: #344054; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
.actions button { background: #f9fafb; color: #344054; font-size: 13px; padding: 6px 10px; }
.actions .danger { color: #b42318; border-color: #fecdca; background: #fff5f6; }
.fila-inactiva { opacity: 0.55; }
.filtro-buscar { margin-bottom: 14px; }
.filtro-buscar input { width: 100%; max-width: 320px; }
.vacio { text-align: center; color: #667085; padding: 24px 0; }

.stock-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; padding: 4px 10px; border-radius: 9999px; font-weight: 700; font-size: 13px; }
.stock-ok { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
.stock-bajo { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
.stock-cero { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }

.modal-overlay { position: fixed; inset: 0; background: rgba(16,24,40,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { background: #fff; border-radius: 16px; padding: 24px; max-width: 420px; width: 90%; }
.modal h3 { margin: 0 0 16px; font-size: 16px; }
.modal-acciones { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
</style>