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

    <!-- ── Formulario Categorías ── -->
    <div class="card">
      <div class="section-title">
        <h2>{{ categoria.IdCategoria ? 'Editar categoría' : 'Nueva categoría' }}</h2>
        <p>Define los rangos permitidos de duración y precio para validar los servicios del catálogo.</p>
      </div>

      <div class="grid">
        <div class="field">
          <span>Nombre de la categoría *</span>
          <input v-model="categoria.Nombre" type="text" placeholder="Ej: Cortes clásicos" />
          <small class="error-msg" v-if="erroresCat.Nombre">{{ erroresCat.Nombre }}</small>
          <small v-else>Escribe el nombre general donde se agruparán los servicios.</small>
        </div>

        <div class="field">
          <span>Duración mínima (min) *</span>
          <input v-model.number="categoria.DuracionMinimaMinutos" type="number" min="1" placeholder="Ej: 25" />
          <small class="error-msg" v-if="erroresCat.DuracionMinimaMinutos">{{ erroresCat.DuracionMinimaMinutos }}</small>
          <small v-else>Tiempo mínimo en minutos para esta categoría.</small>
        </div>

        <div class="field">
          <span>Duración máxima (min) *</span>
          <input
            v-model.number="categoria.DuracionMaximaMinutos"
            type="number"
            :min="categoria.DuracionMinimaMinutos || 1"
            placeholder="Ej: 60"
          />
          <small class="error-msg" v-if="erroresCat.DuracionMaximaMinutos">{{ erroresCat.DuracionMaximaMinutos }}</small>
          <small v-else-if="categoria.DuracionMaximaMinutos && categoria.DuracionMaximaMinutos < categoria.DuracionMinimaMinutos" class="error-msg">
            ⚠ No puede ser menor que la duración mínima ({{ categoria.DuracionMinimaMinutos }} min).
          </small>
          <small v-else>Tiempo máximo en minutos para esta categoría.</small>
        </div>

        <div class="field">
          <span>Precio mínimo (Bs.) *</span>
          <input v-model.number="categoria.PrecioMin" type="number" min="5" step="0.01" placeholder="Ej: 20.00" />
          <small class="error-msg" v-if="erroresCat.PrecioMin">{{ erroresCat.PrecioMin }}</small>
          <small v-else>Monto mínimo en bolivianos. Mínimo permitido: Bs. 5.00.</small>
        </div>

        <div class="field">
          <span>Precio máximo (Bs.) *</span>
          <input
            v-model.number="categoria.PrecioMax"
            type="number"
            :min="Math.max(categoria.PrecioMin || 5, 5)"
            step="0.01"
            placeholder="Ej: 90.00"
          />
          <small class="error-msg" v-if="erroresCat.PrecioMax">{{ erroresCat.PrecioMax }}</small>
          <small v-else-if="categoria.PrecioMax && categoria.PrecioMax < categoria.PrecioMin" class="error-msg">
            ⚠ No puede ser menor que el precio mínimo (Bs. {{ Number(categoria.PrecioMin).toFixed(2) }}).
          </small>
          <small v-else>Monto máximo en bolivianos para esta categoría.</small>
        </div>

        <div class="field actions-field">
          <span>Acción</span>
          <div style="display:flex; gap:8px;">
            <button type="button" @click="guardarCategoria">
              {{ categoria.IdCategoria ? 'Actualizar categoría' : 'Crear categoría' }}
            </button>
            <button type="button" class="secundario" v-if="categoria.IdCategoria" @click="resetCategoria">
              Cancelar
            </button>
          </div>
          <small>Guarda la categoría para poder asignarla a un servicio.</small>
        </div>
      </div>
    </div>

    <!-- ── Formulario Servicios ── -->
    <div class="card">
      <div class="section-title">
        <h2>{{ servicio.IdServicio ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
        <p>Registra el servicio que el cliente podrá seleccionar durante la reserva.</p>
      </div>

      <div class="grid">
        <div class="field">
          <span>Categoría del servicio *</span>
          <select v-model.number="servicio.IdCategoria">
            <option value="" disabled>Selecciona una categoría registrada</option>
            <option v-for="cat in categorias" :key="cat.IdCategoria" :value="cat.IdCategoria">
              {{ cat.Nombre }}
            </option>
          </select>
          <small class="error-msg" v-if="erroresServ.IdCategoria">{{ erroresServ.IdCategoria }}</small>
          <small v-else>El servicio debe pertenecer a una categoría para validar precio y duración.</small>
        </div>

        <div class="field">
          <span>Nombre del servicio *</span>
          <input v-model="servicio.Nombre" type="text" placeholder="Ej: Corte clásico con tijera" />
          <small class="error-msg" v-if="erroresServ.Nombre">{{ erroresServ.Nombre }}</small>
          <small v-else>Nombre exacto que verá el cliente al reservar.</small>
        </div>

        <div class="field">
          <span>Foto o URL del servicio</span>
          <input v-model="servicio.FotoURL" type="text" placeholder="Ej: https://... o corte-clasico.jpg" />
          <small>Ruta o enlace de la imagen. Puede quedar vacío.</small>
        </div>

        <div class="field">
          <span>Precio del servicio (Bs.) *</span>
          <input
            v-model.number="servicio.Precio"
            type="number"
            :min="categoriaActiva?.PrecioMin ?? 5"
            :max="categoriaActiva?.PrecioMax ?? undefined"
            step="0.01"
            placeholder="Ej: 30.00"
          />
          <small class="error-msg" v-if="erroresServ.Precio">{{ erroresServ.Precio }}</small>
          <small v-else-if="categoriaActiva">
            Rango permitido: Bs. {{ Number(categoriaActiva.PrecioMin).toFixed(2) }} – Bs. {{ Number(categoriaActiva.PrecioMax).toFixed(2) }}
            <span
              v-if="servicio.Precio && (servicio.Precio < categoriaActiva.PrecioMin || servicio.Precio > categoriaActiva.PrecioMax)"
              class="error-msg" style="display:block;"
            >
              ⚠ Fuera del rango de la categoría.
            </span>
          </small>
          <small v-else>Precio en bolivianos. Mínimo permitido: Bs. 5.00.</small>
        </div>

        <div class="field">
          <span>Duración en minutos *</span>
          <input
            v-model.number="servicio.DuracionMinutos"
            type="number"
            :min="categoriaActiva?.DuracionMinimaMinutos ?? 1"
            :max="categoriaActiva?.DuracionMaximaMinutos ?? undefined"
            placeholder="Ej: 45"
          />
          <small class="error-msg" v-if="erroresServ.DuracionMinutos">{{ erroresServ.DuracionMinutos }}</small>
          <small v-else-if="categoriaActiva">
            Rango permitido: {{ categoriaActiva.DuracionMinimaMinutos }} – {{ categoriaActiva.DuracionMaximaMinutos }} min.
            <span
              v-if="servicio.DuracionMinutos && (servicio.DuracionMinutos < categoriaActiva.DuracionMinimaMinutos || servicio.DuracionMinutos > categoriaActiva.DuracionMaximaMinutos)"
              class="error-msg" style="display:block;"
            >
              ⚠ Fuera del rango de la categoría.
            </span>
          </small>
          <small v-else>Tiempo de bloqueo de la cita en minutos.</small>
        </div>

        <div class="field actions-field">
          <span>Acción</span>
          <div style="display:flex; gap:8px;">
            <button type="button" @click="guardarServicio">
              {{ servicio.IdServicio ? 'Actualizar servicio' : 'Crear servicio' }}
            </button>
            <button type="button" class="secundario" v-if="servicio.IdServicio" @click="resetServicio">
              Cancelar
            </button>
          </div>
          <small>Guarda el servicio en el catálogo activo del sistema.</small>
        </div>
      </div>
    </div>

    <!-- ── Listado ── -->
    <div class="card">
      <div class="section-title">
        <h2>Servicios registrados</h2>
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

      <p v-if="!loading && servicios.length === 0" style="text-align:center; color:#667085; padding:24px 0;">
        No hay servicios registrados todavía.
      </p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from "vue"
import AlertMessage from "../../components/common/AlertMessage.vue"
import LoadingSpinner from "../../components/common/LoadingSpinner.vue"
import { serviciosService } from "../../services/serviciosService"

const categorias = ref([])
const servicios  = ref([])
const loading    = ref(false)
const message    = ref("")
const messageType = ref("info")

const erroresCat  = ref({})
const erroresServ = ref({})

const categoria = reactive({
  IdCategoria: null,
  Nombre: "",
  DuracionMinimaMinutos: 1,
  DuracionMaximaMinutos: 30,
  PrecioMin: 5,
  PrecioMax: 0,
})

const servicio = reactive({
  IdServicio: null,
  IdCategoria: "",
  Nombre: "",
  FotoURL: "",
  Precio: 0,
  DuracionMinutos: 30,
})

const categoriaActiva = computed(() =>
  categorias.value.find(c => c.IdCategoria === servicio.IdCategoria) || null
)

const notify = (text, type = "success") => {
  message.value = text
  messageType.value = type
}

async function cargarTodo() {
  loading.value = true
  try {
    const [catRes, servRes] = await Promise.all([
      serviciosService.listarCategorias(),
      serviciosService.listarServicios(),
    ])
    categorias.value = catRes.data.data
    servicios.value  = servRes.data.data
  } catch (error) {
    notify(error.response?.data?.message || "Error al cargar catálogo", "error")
  } finally {
    loading.value = false
  }
}

// ── Validación de categoría ──────────────────────────────────────
function validarCategoria() {
  const e = {}
  if (!categoria.Nombre?.trim()) {
    e.Nombre = 'El nombre de la categoría es obligatorio.'
  }
  if (!categoria.DuracionMinimaMinutos || categoria.DuracionMinimaMinutos < 1) {
    e.DuracionMinimaMinutos = 'Ingresa una duración mínima válida (mínimo 1 min).'
  }
  if (!categoria.DuracionMaximaMinutos || categoria.DuracionMaximaMinutos < 1) {
    e.DuracionMaximaMinutos = 'Ingresa una duración máxima válida.'
  } else if (Number(categoria.DuracionMaximaMinutos) < Number(categoria.DuracionMinimaMinutos)) {
    e.DuracionMaximaMinutos = 'La duración máxima no puede ser menor que la mínima.'
  }
  if (!categoria.PrecioMin || Number(categoria.PrecioMin) < 5) {
    e.PrecioMin = 'El precio mínimo debe ser al menos Bs. 5.00.'
  }
  if (!categoria.PrecioMax || Number(categoria.PrecioMax) < 5) {
    e.PrecioMax = 'El precio máximo debe ser al menos Bs. 5.00.'
  } else if (Number(categoria.PrecioMax) < Number(categoria.PrecioMin)) {
    e.PrecioMax = 'El precio máximo no puede ser menor que el mínimo.'
  }
  erroresCat.value = e
  return Object.keys(e).length === 0
}

// ── Validación de servicio ───────────────────────────────────────
function validarServicio() {
  const e = {}
  if (!servicio.IdCategoria) {
    e.IdCategoria = 'Selecciona una categoría.'
  }
  if (!servicio.Nombre?.trim()) {
    e.Nombre = 'El nombre del servicio es obligatorio.'
  }
  if (!servicio.Precio || Number(servicio.Precio) < 5) {
    e.Precio = 'El precio debe ser al menos Bs. 5.00.'
  } else if (categoriaActiva.value) {
    if (Number(servicio.Precio) < Number(categoriaActiva.value.PrecioMin)) {
      e.Precio = `El precio no puede ser menor al mínimo de la categoría (Bs. ${Number(categoriaActiva.value.PrecioMin).toFixed(2)}).`
    } else if (Number(servicio.Precio) > Number(categoriaActiva.value.PrecioMax)) {
      e.Precio = `El precio no puede superar el máximo de la categoría (Bs. ${Number(categoriaActiva.value.PrecioMax).toFixed(2)}).`
    }
  }
  if (!servicio.DuracionMinutos || servicio.DuracionMinutos < 1) {
    e.DuracionMinutos = 'Ingresa una duración válida (mínimo 1 min).'
  } else if (categoriaActiva.value) {
    if (Number(servicio.DuracionMinutos) < Number(categoriaActiva.value.DuracionMinimaMinutos)) {
      e.DuracionMinutos = `La duración no puede ser menor al mínimo de la categoría (${categoriaActiva.value.DuracionMinimaMinutos} min).`
    } else if (Number(servicio.DuracionMinutos) > Number(categoriaActiva.value.DuracionMaximaMinutos)) {
      e.DuracionMinutos = `La duración no puede superar el máximo de la categoría (${categoriaActiva.value.DuracionMaximaMinutos} min).`
    }
  }
  erroresServ.value = e
  return Object.keys(e).length === 0
}

function resetCategoria() {
  Object.assign(categoria, {
    IdCategoria: null,
    Nombre: "",
    DuracionMinimaMinutos: 1,
    DuracionMaximaMinutos: 30,
    PrecioMin: 5,
    PrecioMax: 0,
  })
  erroresCat.value = {}
}

function resetServicio() {
  Object.assign(servicio, {
    IdServicio: null,
    IdCategoria: "",
    Nombre: "",
    FotoURL: "",
    Precio: 0,
    DuracionMinutos: 30,
  })
  erroresServ.value = {}
}

async function guardarCategoria() {
  if (!validarCategoria()) return
  try {
    if (categoria.IdCategoria) {
      await serviciosService.actualizarCategoria(categoria.IdCategoria, categoria)
    } else {
      await serviciosService.crearCategoria(categoria)
    }
    notify('Categoría guardada correctamente')
    resetCategoria()
    await cargarTodo()
  } catch (error) {
    notify(error.response?.data?.message || 'No se pudo guardar la categoría', 'error')
  }
}

async function guardarServicio() {
  if (!validarServicio()) return
  try {
    if (servicio.IdServicio) {
      await serviciosService.actualizarServicio(servicio.IdServicio, servicio)
    } else {
      await serviciosService.crearServicio(servicio)
    }
    notify('Servicio guardado correctamente')
    resetServicio()
    await cargarTodo()
  } catch (error) {
    notify(error.response?.data?.message || 'No se pudo guardar el servicio', 'error')
  }
}

function editarServicio(item) {
  Object.assign(servicio, item)
  erroresServ.value = {}
}

async function desactivarServicio(id) {
  if (!confirm('¿Desactivar este servicio?')) return
  try {
    await serviciosService.desactivarServicio(id)
    notify('Servicio desactivado correctamente')
    await cargarTodo()
  } catch (error) {
    notify(
      error.response?.data?.message || error.friendlyMessage || 'No se pudo desactivar el servicio',
      'error'
    )
  }
}

onMounted(cargarTodo)
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
.field small { color: #667085; line-height: 1.35; font-size: 12px; }
.error-msg { color: #b42318 !important; font-size: 12px; }
input, select, button { border: 1px solid #d0d5dd; border-radius: 10px; padding: 10px 12px; }
input:focus, select:focus { outline: 2px solid #b2ddff; border-color: #53b1fd; }
button { cursor: pointer; font-weight: 700; background: #111827; color: #fff; }
button.secundario { background: #f9fafb; color: #344054; }
.actions-field button { min-height: 42px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #eaecf0; text-align: left; }
.actions { display: flex; gap: 8px; }
.actions button { background: #f9fafb; color: #344054; }
.actions .danger { color: #b42318; border-color: #fecdca; background: #fff5f6; }
</style>