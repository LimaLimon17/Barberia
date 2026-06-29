<template>
  <section class="page">
    <header class="page-header">
      <div>
        <h1>Gestión de Inventario</h1>
        <p>Registra productos, costos, porcentajes, precio de venta y stock disponible.</p>
      </div>
      <button @click="resetForm">Nuevo producto</button>
    </header>

    <AlertMessage :message="message" :type="messageType" />
    <LoadingSpinner :show="loading" />

    <form class="card grid" @submit.prevent="guardarProducto">
      <label class="field">
        <span>Nombre del producto</span>
        <input
          v-model="form.Nombre"
          type="text"
          placeholder="Ej: Gel fijador, cera capilar, shampoo"
          required
        />
        <small>Nombre comercial del producto que venderán los barberos.</small>
      </label>

      <label class="field">
        <span>Costo de compra</span>
        <input
          v-model.number="form.CostoCompra"
          type="number"
          min="0"
          step="0.01"
          placeholder="Ej: 35.00"
          required
        />
        <small>Costo unitario pagado por la barbería para calcular ganancia y precio de venta.</small>
      </label>

      <label class="field">
        <span>Precio de venta</span>
        <input
          v-model.number="form.PrecioVenta"
          type="number"
          min="0"
          step="0.01"
          placeholder="Ej: 42.00"
          required
        />
        <small>Precio final visible para el barbero al registrar la venta.</small>
      </label>

      <label class="field">
        <span>Porcentaje de venta</span>
        <input
          v-model.number="form.PorcentajeVenta"
          type="number"
          min="10"
          step="0.01"
          placeholder="Ej: 20"
          required
        />
        <small>Margen aplicado sobre el costo de compra. El mínimo permitido es 10%.</small>
      </label>

      <label class="field">
        <span>Porcentaje de comisión del barbero</span>
        <input
          v-model.number="form.PorcentajeBarbero"
          type="number"
          min="0"
          step="0.01"
          placeholder="Ej: 10"
          required
        />
        <small>Parte del margen que corresponde al barbero por vender este producto.</small>
      </label>

      <label class="field">
        <span>Stock actual o cantidad inicial</span>
        <input
          v-model.number="form.StockActual"
          type="number"
          min="0"
          placeholder="Ej: 25"
          required
        />
        <small>Cantidad disponible. El sistema la disminuirá automáticamente con cada venta.</small>
      </label>

      <div class="field actions-field">
        <span>Acción del producto</span>
        <button type="submit">{{ form.IdProducto ? 'Actualizar producto' : 'Guardar producto' }}</button>
        <small>Guarda el producto con su stock, costo, precio y porcentajes vigentes.</small>
      </div>
    </form>

    <div class="card lote-card" v-if="productoSeleccionado">
      <div class="section-title">
        <h2>Registrar lote para {{ productoSeleccionado.Nombre }}</h2>
        <p>Agrega nueva mercancía al inventario del producto seleccionado.</p>
      </div>

      <form class="grid" @submit.prevent="registrarLote">
        <label class="field">
          <span>Cantidad recibida en el lote</span>
          <input
            v-model.number="lote.CantidadRecibida"
            type="number"
            min="1"
            placeholder="Ej: 12"
            required
          />
          <small>Número de unidades que ingresan al inventario.</small>
        </label>

        <label class="field">
          <span>Costo unitario del lote</span>
          <input
            v-model.number="lote.CostoUnitario"
            type="number"
            min="0"
            step="0.01"
            placeholder="Ej: 35.00"
            required
          />
          <small>Costo de compra por unidad para este ingreso de mercancía.</small>
        </label>

        <label class="field">
          <span>Fecha y hora de ingreso</span>
          <input
            v-model="lote.FechaIngreso"
            type="datetime-local"
          />
          <small>Fecha en la que se recibió el lote. Si se deja vacío, el backend puede usar la fecha actual.</small>
        </label>

        <div class="field actions-field">
          <span>Acción del lote</span>
          <button type="submit">Agregar stock</button>
          <small>Registra el lote y suma la cantidad al stock actual.</small>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="section-title">
        <h2>Productos registrados</h2>
        <p>El stock en amarillo indica alerta baja y en rojo indica stock crítico.</p>
      </div>

      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Costo</th>
            <th>Precio</th>
            <th>% Venta</th>
            <th>% Barbero</th>
            <th>Stock</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="producto in productos" :key="producto.IdProducto">
            <td>{{ producto.Nombre }}</td>
            <td>{{ money(producto.CostoCompra) }}</td>
            <td>{{ money(producto.PrecioVenta) }}</td>
            <td>{{ producto.PorcentajeVenta }}%</td>
            <td>{{ producto.PorcentajeBarbero }}%</td>
            <td :class="stockClass(producto.StockActual)">{{ producto.StockActual }}</td>
            <td class="actions">
              <button @click="editar(producto)">Editar</button>
              <button @click="seleccionarLote(producto)">Lote</button>
              <button class="danger" @click="desactivar(producto.IdProducto)">Desactivar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AlertMessage from "../../components/common/AlertMessage.vue";
import LoadingSpinner from "../../components/common/LoadingSpinner.vue";
import { productosService } from "../../services/productosService";

const productos = ref([]);
const loading = ref(false);
const message = ref("");
const messageType = ref("info");
const productoSeleccionado = ref(null);

const form = reactive({
  IdProducto: null,
  Nombre: "",
  CostoCompra: 0,
  PrecioVenta: 0,
  PorcentajeVenta: 10,
  PorcentajeBarbero: 5,
  StockActual: 0,
});

const lote = reactive({
  CantidadRecibida: 1,
  CostoUnitario: 0,
  FechaIngreso: "",
});

const money = (value) => `Bs ${Number(value || 0).toFixed(2)}`;
const notify = (text, type = "success") => {
  message.value = text;
  messageType.value = type;
};
const stockClass = (stock) => ({ critical: Number(stock) === 0, low: Number(stock) > 0 && Number(stock) <= 5 });

async function cargarProductos() {
  loading.value = true;
  try {
    const { data } = await productosService.listar();
    productos.value = data.data;
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo cargar productos", "error");
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

function editar(producto) {
  Object.assign(form, producto);
}

function seleccionarLote(producto) {
  productoSeleccionado.value = producto;
  lote.CantidadRecibida = 1;
  lote.CostoUnitario = Number(producto.CostoCompra || 0);
  lote.FechaIngreso = "";
}

async function guardarProducto() {
  try {
    if (form.IdProducto) {
      await productosService.actualizar(form.IdProducto, form);
      notify("Producto actualizado correctamente");
    } else {
      await productosService.crear(form);
      notify("Producto creado correctamente");
    }
    resetForm();
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo guardar el producto", "error");
  }
}

async function registrarLote() {
  if (!productoSeleccionado.value) return;
  try {
    await productosService.registrarLote(productoSeleccionado.value.IdProducto, lote);
    notify("Lote registrado y stock actualizado");
    productoSeleccionado.value = null;
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo registrar el lote", "error");
  }
}

async function desactivar(id) {
  if (!confirm("¿Desactivar este producto?")) return;
  try {
    await productosService.desactivar(id);
    notify("Producto desactivado");
    await cargarProductos();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo desactivar", "error");
  }
}

onMounted(cargarProductos);
</script>

<style scoped>
.page { padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
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
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
.actions button { background: #f9fafb; color: #344054; }
.danger { color: #b42318; border-color: #fecdca; background: #fff5f6; }
.low { color: #b54708; font-weight: 800; background: #fffaeb; }
.critical { color: #b42318; font-weight: 800; background: #fff1f3; }
</style>
