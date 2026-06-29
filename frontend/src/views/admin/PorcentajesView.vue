<template>
  <section class="page">
    <header class="page-header">
      <div>
        <h1>Porcentajes de Productos</h1>
        <p>Actualiza el porcentaje de venta y la comisión del barbero sin afectar ventas anteriores.</p>
      </div>
    </header>

    <AlertMessage :message="message" :type="messageType" />

    <div class="card grid">
      <label class="field">
        <span>Producto a modificar</span>
        <select v-model.number="productoId" @change="cargarHistorial" required>
          <option value="" disabled>Selecciona un producto registrado</option>
          <option v-for="p in productos" :key="p.IdProducto" :value="p.IdProducto">
            {{ p.Nombre }} - Bs {{ Number(p.PrecioVenta).toFixed(2) }}
          </option>
        </select>
        <small>Elige el producto al que se le cambiarán precio y porcentajes.</small>
      </label>

      <label class="field">
        <span>Nuevo precio de venta</span>
        <input
          v-model.number="form.PrecioVenta"
          type="number"
          step="0.01"
          min="0"
          placeholder="Ej: 42.00"
        />
        <small>Precio final que verá el barbero al vender el producto.</small>
      </label>

      <label class="field">
        <span>Nuevo porcentaje de venta</span>
        <input
          v-model.number="form.PorcentajeVenta"
          type="number"
          step="0.01"
          min="10"
          placeholder="Ej: 20"
        />
        <small>Margen sobre el costo de compra. Debe ser mínimo 10%.</small>
      </label>

      <label class="field">
        <span>Nuevo porcentaje de comisión del barbero</span>
        <input
          v-model.number="form.PorcentajeBarbero"
          type="number"
          step="0.01"
          min="0"
          placeholder="Ej: 10"
        />
        <small>No debe igualar ni superar el porcentaje total de venta.</small>
      </label>

      <label class="field">
        <span>Fecha de inicio del nuevo porcentaje</span>
        <input v-model="form.FechaInicio" type="date" />
        <small>Desde esta fecha se aplicarán los nuevos valores. El período anterior se cerrará.</small>
      </label>

      <div class="field actions-field">
        <span>Acción de porcentajes</span>
        <button type="button" @click="actualizar">Actualizar porcentajes</button>
        <small>Guarda un nuevo historial sin modificar las ventas pasadas.</small>
      </div>
    </div>

    <div class="card" v-if="historial.length">
      <div class="section-title">
        <h2>Historial de porcentajes</h2>
        <p>Revisa períodos anteriores y el período vigente del producto seleccionado.</p>
      </div>

      <table>
        <thead>
          <tr>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Precio</th>
            <th>% Venta</th>
            <th>% Barbero</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="h in historial" :key="h.IdHistorial">
            <td>{{ h.FechaInicio }}</td>
            <td>{{ h.FechaFin || 'Vigente' }}</td>
            <td>Bs {{ Number(h.PrecioVenta || 0).toFixed(2) }}</td>
            <td>{{ h.PorcentajeVenta }}%</td>
            <td>{{ h.PorcentajeBarbero }}%</td>
            <td>{{ h.EstadoA === 0 ? 'Cerrado' : 'Activo' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AlertMessage from "../../components/common/AlertMessage.vue";
import { productosService } from "../../services/productosService";
import { porcentajesService } from "../../services/porcentajesService";

const productos = ref([]);
const productoId = ref("");
const historial = ref([]);
const message = ref("");
const messageType = ref("info");

const form = reactive({
  PrecioVenta: 0,
  PorcentajeVenta: 10,
  PorcentajeBarbero: 5,
  FechaInicio: new Date().toISOString().slice(0, 10),
});

const notify = (text, type = "success") => {
  message.value = text;
  messageType.value = type;
};

async function cargarProductos() {
  const { data } = await productosService.listar();
  productos.value = data.data;
}

async function cargarHistorial() {
  if (!productoId.value) return;
  const { data } = await porcentajesService.historialProducto(productoId.value);
  historial.value = data.data.historial;
  const producto = data.data.producto;
  Object.assign(form, {
    PrecioVenta: Number(producto.PrecioVenta),
    PorcentajeVenta: Number(producto.PorcentajeVenta),
    PorcentajeBarbero: Number(producto.PorcentajeBarbero),
    FechaInicio: new Date().toISOString().slice(0, 10),
  });
}

async function actualizar() {
  if (!productoId.value) return notify("Selecciona un producto", "warning");
  try {
    await porcentajesService.actualizarProducto(productoId.value, form);
    notify("Porcentajes actualizados");
    await cargarProductos();
    await cargarHistorial();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo actualizar", "error");
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
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
</style>
