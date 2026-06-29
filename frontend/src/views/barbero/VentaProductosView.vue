<template>
  <section class="page">
    <h1>Venta de Productos</h1>

    <AlertMessage :message="message" :type="messageType" />
    <LoadingSpinner :show="loading" />

    <div class="card grid">
      <select v-model.number="venta.IdBarbero" required>
        <option value="" disabled>Barbero</option>
        <option v-for="b in catalogo.barberos" :key="b.IdBarbero" :value="b.IdBarbero">
          {{ b.usuario?.Nombre1 }} {{ b.usuario?.Apellido1 }}
        </option>
      </select>
      <select v-model="venta.IdCliente">
        <option value="">Cliente</option>
        <option v-for="c in catalogo.clientes" :key="c.CI" :value="c.CI">
          {{ c.Nombre1 }} {{ c.Apellido1 }} - {{ c.CI }}
        </option>
      </select>
      <select v-model.number="venta.IdReserva">
        <option value="">Reserva</option>
        <option v-for="r in catalogo.reservas" :key="r.IdReserva" :value="r.IdReserva">
          #{{ r.IdReserva }} - {{ r.FechaCita }} {{ r.HoraInicio }}
        </option>
      </select>
      <input v-model="venta.MetodoPago" placeholder="Método de pago: efectivo, QR" />
    </div>

    <div class="card">
      <h2>Agregar productos</h2>
      <div class="grid">
        <select v-model.number="item.IdProducto">
          <option value="" disabled>Producto</option>
          <option v-for="p in catalogo.productos" :key="p.IdProducto" :value="p.IdProducto">
            {{ p.Nombre }} - Stock {{ p.StockActual }} - Bs {{ Number(p.PrecioVenta).toFixed(2) }}
          </option>
        </select>
        <input v-model.number="item.Cantidad" type="number" min="1" placeholder="Cantidad" />
        <button @click="agregarItem">Agregar</button>
      </div>

      <table v-if="venta.items.length">
        <thead>
          <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="(it, index) in venta.items" :key="index">
            <td>{{ nombreProducto(it.IdProducto) }}</td>
            <td>{{ it.Cantidad }}</td>
            <td>Bs {{ precioProducto(it.IdProducto).toFixed(2) }}</td>
            <td>Bs {{ (precioProducto(it.IdProducto) * it.Cantidad).toFixed(2) }}</td>
            <td><button class="danger" @click="venta.items.splice(index, 1)">Quitar</button></td>
          </tr>
        </tbody>
      </table>

      <h3>Total: Bs {{ total.toFixed(2) }}</h3>
      <button @click="registrarVenta" :disabled="!venta.items.length">Registrar venta</button>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import AlertMessage from "../../components/common/AlertMessage.vue";
import LoadingSpinner from "../../components/common/LoadingSpinner.vue";
import { ventasProductosService } from "../../services/ventasProductosService";

const loading = ref(false);
const message = ref("");
const messageType = ref("info");
const catalogo = reactive({ productos: [], barberos: [], clientes: [], reservas: [] });
const venta = reactive({ IdBarbero: "", IdCliente: "", IdReserva: "", MetodoPago: "", items: [] });
const item = reactive({ IdProducto: "", Cantidad: 1 });
const notify = (text, type = "success") => { message.value = text; messageType.value = type; };

const productoMap = computed(() => new Map(catalogo.productos.map((p) => [p.IdProducto, p])));
const precioProducto = (id) => Number(productoMap.value.get(id)?.PrecioVenta || 0);
const nombreProducto = (id) => productoMap.value.get(id)?.Nombre || id;
const total = computed(() => venta.items.reduce((sum, it) => sum + precioProducto(it.IdProducto) * Number(it.Cantidad || 0), 0));

async function cargarCatalogo() {
  loading.value = true;
  try {
    const { data } = await ventasProductosService.catalogo();
    Object.assign(catalogo, data.data);
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo cargar catálogo", "error");
  } finally {
    loading.value = false;
  }
}

function agregarItem() {
  if (!item.IdProducto || item.Cantidad < 1) return notify("Selecciona producto y cantidad", "warning");
  const existente = venta.items.find((x) => x.IdProducto === item.IdProducto);
  if (existente) existente.Cantidad += item.Cantidad;
  else venta.items.push({ IdProducto: item.IdProducto, Cantidad: item.Cantidad });
  item.IdProducto = "";
  item.Cantidad = 1;
}

async function registrarVenta() {
  if (!venta.IdBarbero) return notify("Selecciona barbero", "warning");
  try {
    const payload = {
      ...venta,
      IdCliente: venta.IdCliente || null,
      IdReserva: venta.IdReserva || null,
      MetodoPago: venta.MetodoPago || null,
    };
    await ventasProductosService.registrar(payload);
    notify("Venta registrada correctamente");
    Object.assign(venta, { IdBarbero: "", IdCliente: "", IdReserva: "", MetodoPago: "", items: [] });
    await cargarCatalogo();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo registrar venta", "error");
  }
}

onMounted(cargarCatalogo);
</script>

<style scoped>
.page { padding: 24px; }
.card { background: #fff; border: 1px solid #e4e7ec; border-radius: 16px; padding: 18px; margin-top: 16px; box-shadow: 0 8px 24px rgba(16,24,40,.06); }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
input, select, button { border: 1px solid #d0d5dd; border-radius: 10px; padding: 10px 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
.danger { color: #b42318; }
</style>
