<template>
  <section class="page">
    <header class="page-header">
      <div>
        <h1>Catálogo de Servicios</h1>
        <p>Administra las categorías y los servicios específicos que estarán disponibles para las reservas.</p>
      </div>
    </header>

    <AlertMessage :mensaje="message" :tipo="messageType" />
    <LoadingSpinner :show="loading" />

    <div class="card">
      <div class="section-title">
        <h2>Categorías de servicios</h2>
        <p>Define los rangos permitidos de duración y precio para validar los servicios del catálogo.</p>
      </div>

      <form class="grid" @submit.prevent="guardarCategoria">
        <label class="field">
          <span>Nombre de la categoría</span>
          <input
            v-model="categoria.Nombre"
            type="text"
            placeholder="Ej: Cortes clásicos, Cortes modernos, Ondulación"
            required
          />
          <small>Escribe el nombre general donde se agruparán los servicios.</small>
        </label>

        <label class="field">
          <span>Duración mínima permitida</span>
          <input
            v-model.number="categoria.DuracionMinimaMinutos"
            type="number"
            min="1"
            placeholder="Ej: 25"
            required
          />
          <small>Tiempo mínimo en minutos que puede durar un servicio de esta categoría.</small>
        </label>

        <label class="field">
  <span>Duración máxima permitida</span>
  <input
    v-model.number="categoria.DuracionMaximaMinutos"
    type="number"
    :min="categoria.DuracionMinimaMinutos || 1"
    placeholder="Ej: 35"
    required
  />
  <small>
    Tiempo máximo en minutos permitido para esta categoría.
    <span
      v-if="categoria.DuracionMaximaMinutos && categoria.DuracionMaximaMinutos < categoria.DuracionMinimaMinutos"
      style="color:#b42318; display:block; margin-top:2px;"
    >
      ⚠ No puede ser menor que la duración mínima ({{ categoria.DuracionMinimaMinutos }} min).
    </span>
  </small>
</label>

<label class="field">
  <span>Precio máximo permitido</span>
  <input
    v-model.number="categoria.PrecioMax"
    type="number"
    :min="categoria.PrecioMin || 0"
    step="0.01"
    placeholder="Ej: 90.00"
    required
  />
  <small>
    Monto máximo en bolivianos permitido para esta categoría.
    <span
      v-if="categoria.PrecioMax && categoria.PrecioMax < categoria.PrecioMin"
      style="color:#b42318; display:block; margin-top:2px;"
    >
      ⚠ No puede ser menor que el precio mínimo (Bs {{ Number(categoria.PrecioMin).toFixed(2) }}).
    </span>
  </small>
</label>
        <div class="field actions-field">
          <span>Acción de categoría</span>
          <button type="submit">{{ categoria.IdCategoria ? 'Actualizar categoría' : 'Crear categoría' }}</button>
          <small>Guarda la categoría para poder asignarla a un servicio.</small>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="section-title">
        <h2>Servicios específicos</h2>
        <p>Registra el servicio que el cliente podrá seleccionar durante la reserva.</p>
      </div>

      <form class="grid" @submit.prevent="guardarServicio">
        <label class="field">
          <span>Categoría del servicio</span>
          <select v-model.number="servicio.IdCategoria" required>
            <option value="" disabled>Selecciona una categoría registrada</option>
            <option v-for="cat in categorias" :key="cat.IdCategoria" :value="cat.IdCategoria">
              {{ cat.Nombre }}
            </option>
          </select>
          <small>El servicio debe pertenecer a una categoría para validar precio y duración.</small>
        </label>

        <label class="field">
          <span>Nombre del servicio</span>
          <input
            v-model="servicio.Nombre"
            type="text"
            placeholder="Ej: Corte clásico con tijera"
            required
          />
          <small>Nombre exacto que verá el cliente al reservar.</small>
        </label>

        <label class="field">
          <span>Foto o URL del servicio</span>
          <input
            v-model="servicio.FotoURL"
            type="text"
            placeholder="Ej: corte-clasico.jpg o https://..."
          />
          <small>Ruta o enlace de la imagen que representa el servicio. Puede quedar vacío.</small>
        </label>

        <label class="field">
  <span>Precio del servicio</span>
  <input
    v-model.number="servicio.Precio"
    type="number"
    :min="categoriaActiva?.PrecioMin ?? 0"
    :max="categoriaActiva?.PrecioMax ?? undefined"
    step="0.01"
    placeholder="Ej: 30.00"
    required
  />
  <small>
    Precio en bolivianos.
    <span v-if="categoriaActiva">
      Rango permitido: Bs {{ Number(categoriaActiva.PrecioMin).toFixed(2) }} – Bs {{ Number(categoriaActiva.PrecioMax).toFixed(2) }}.
    </span>
    <span
      v-if="categoriaActiva && servicio.Precio && (servicio.Precio < categoriaActiva.PrecioMin || servicio.Precio > categoriaActiva.PrecioMax)"
      style="color:#b42318; display:block; margin-top:2px;"
    >
      ⚠ El precio está fuera del rango de la categoría.
    </span>
  </small>
</label>

<label class="field">
  <span>Duración fija en minutos</span>
  <input
    v-model.number="servicio.DuracionMinutos"
    type="number"
    :min="categoriaActiva?.DuracionMinimaMinutos ?? 1"
    :max="categoriaActiva?.DuracionMaximaMinutos ?? undefined"
    placeholder="Ej: 45"
    required
  />
  <small>
    Tiempo que se usará para calcular el bloqueo de la cita.
    <span v-if="categoriaActiva">
      Rango permitido: {{ categoriaActiva.DuracionMinimaMinutos }} – {{ categoriaActiva.DuracionMaximaMinutos }} min.
    </span>
    <span
      v-if="categoriaActiva && servicio.DuracionMinutos && (servicio.DuracionMinutos < categoriaActiva.DuracionMinimaMinutos || servicio.DuracionMinutos > categoriaActiva.DuracionMaximaMinutos)"
      style="color:#b42318; display:block; margin-top:2px;"
    >
      ⚠ La duración está fuera del rango de la categoría.
    </span>
  </small>
</label>

        <div class="field actions-field">
          <span>Acción de servicio</span>
          <button type="submit">{{ servicio.IdServicio ? 'Actualizar servicio' : 'Crear servicio' }}</button>
          <small>Guarda el servicio en el catálogo activo del sistema.</small>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="section-title">
        <h2>Listado de servicios registrados</h2>
        <p>Desde aquí puedes editar o desactivar servicios sin borrar su historial.</p>
      </div>

      <table>
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Duración</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in servicios" :key="item.IdServicio">
            <td>{{ item.Nombre }}</td>
            <td>{{ item.categoria?.Nombre || item.IdCategoria }}</td>
            <td>Bs {{ Number(item.Precio || 0).toFixed(2) }}</td>
            <td>{{ item.DuracionMinutos }} min</td>
            <td class="actions">
              <button @click="editarServicio(item)">Editar</button>
              <button class="danger" @click="desactivarServicio(item.IdServicio)">Desactivar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from "vue"
import AlertMessage from "../../components/common/AlertMessage.vue";
import LoadingSpinner from "../../components/common/LoadingSpinner.vue";
import { serviciosService } from "../../services/serviciosService";



const categorias = ref([]);
const servicios = ref([]);
const loading = ref(false);
const message = ref("");
const messageType = ref("info");
const categoriaActiva = computed(() =>
  categorias.value.find(c => c.IdCategoria === servicio.IdCategoria) || null
)

const categoria = reactive({
  IdCategoria: null,
  Nombre: "",
  DuracionMinimaMinutos: 1,
  DuracionMaximaMinutos: 30,
  PrecioMin: 0,
  PrecioMax: 0,
});

const servicio = reactive({
  IdServicio: null,
  IdCategoria: "",
  Nombre: "",
  FotoURL: "",
  Precio: 0,
  DuracionMinutos: 30,
});

const notify = (text, type = "success") => {
  message.value = text;
  messageType.value = type;
};

async function cargarTodo() {
  loading.value = true;
  try {
    const [catRes, servRes] = await Promise.all([
      serviciosService.listarCategorias(),
      serviciosService.listarServicios(),
    ]);
    categorias.value = catRes.data.data;
    servicios.value = servRes.data.data;
  } catch (error) {
    notify(error.friendlyMessage || "Error al cargar catálogo", "error");
  } finally {
    loading.value = false;
  }
}

function resetCategoria() {
  Object.assign(categoria, {
    IdCategoria: null,
    Nombre: "",
    DuracionMinimaMinutos: 1,
    DuracionMaximaMinutos: 30,
    PrecioMin: 0,
    PrecioMax: 0,
  });
}

function resetServicio() {
  Object.assign(servicio, {
    IdServicio: null,
    IdCategoria: "",
    Nombre: "",
    FotoURL: "",
    Precio: 0,
    DuracionMinutos: 30,
  });
}

async function guardarCategoria() {
  // Validación frontend antes de llamar al backend
  if (Number(categoria.DuracionMaximaMinutos) < Number(categoria.DuracionMinimaMinutos)) {
    notify('La duración máxima no puede ser menor que la mínima.', 'error')
    return
  }
  if (Number(categoria.PrecioMax) < Number(categoria.PrecioMin)) {
    notify('El precio máximo no puede ser menor que el mínimo.', 'error')
    return
  }

  try {
    if (categoria.IdCategoria) {
      await serviciosService.actualizarCategoria(categoria.IdCategoria, categoria)
    } else {
      await serviciosService.crearCategoria(categoria)
    }
    notify('Categoría guardada')
    resetCategoria()
    await cargarTodo()
  } catch (error) {
    notify(error.friendlyMessage || error.response?.data?.message || 'No se pudo guardar la categoría', 'error')
  }
}

async function guardarServicio() {
  // Buscar la categoría seleccionada para validar rangos
  const catSeleccionada = categorias.value.find(c => c.IdCategoria === servicio.IdCategoria)

  if (catSeleccionada) {
    if (Number(servicio.Precio) < Number(catSeleccionada.PrecioMin)) {
      notify(`El precio no puede ser menor al mínimo de la categoría (Bs ${Number(catSeleccionada.PrecioMin).toFixed(2)}).`, 'error')
      return
    }
    if (Number(servicio.Precio) > Number(catSeleccionada.PrecioMax)) {
      notify(`El precio no puede superar el máximo de la categoría (Bs ${Number(catSeleccionada.PrecioMax).toFixed(2)}).`, 'error')
      return
    }
    if (Number(servicio.DuracionMinutos) < Number(catSeleccionada.DuracionMinimaMinutos)) {
      notify(`La duración no puede ser menor al mínimo de la categoría (${catSeleccionada.DuracionMinimaMinutos} min).`, 'error')
      return
    }
    if (Number(servicio.DuracionMinutos) > Number(catSeleccionada.DuracionMaximaMinutos)) {
      notify(`La duración no puede superar el máximo de la categoría (${catSeleccionada.DuracionMaximaMinutos} min).`, 'error')
      return
    }
  }

  try {
    if (servicio.IdServicio) {
      await serviciosService.actualizarServicio(servicio.IdServicio, servicio)
    } else {
      await serviciosService.crearServicio(servicio)
    }
    notify('Servicio guardado')
    resetServicio()
    await cargarTodo()
  } catch (error) {
    notify(error.friendlyMessage || error.response?.data?.message || 'No se pudo guardar el servicio', 'error')
  }
}

function editarServicio(item) {
  Object.assign(servicio, item);
}

async function desactivarServicio(id) {
  if (!confirm("¿Desactivar este servicio?")) return;
  try {
    await serviciosService.desactivarServicio(id);
    notify("Servicio desactivado");
    await cargarTodo();
  } catch (error) {
    notify(error.friendlyMessage || "No se pudo desactivar", "error");
  }
}

onMounted(cargarTodo);
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
.actions-field button { min-height: 42px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
.actions { display: flex; gap: 8px; }
.actions button { background: #f9fafb; color: #344054; }
.actions .danger, .danger { color: #b42318; border-color: #fecdca; background: #fff5f6; }
</style>
