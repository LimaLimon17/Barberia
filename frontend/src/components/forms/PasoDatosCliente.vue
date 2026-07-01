<template>
  <section class="paso">
    <h2 class="paso-titulo">Tus datos</h2>
    <p class="paso-sub">Ingresa tu CI para ver si ya tenemos tus datos registrados.</p>

    <div class="campo">
      <label>Cédula de identidad</label>
      <div class="fila-ci">
       <input
  v-model="ciInput"
  type="text"
  inputmode="numeric"
  placeholder="Ej: 1000001"
  maxlength="11"
  :class="['input-field', tocado.CI && errorCI ? 'input-error' : '']"
  @blur="tocado.CI = true"
  @input="resetearBusqueda"
  @keydown="soloNumerosYGuion"
/>
        <button
          class="btn-secundario"
          :disabled="!ciInput || errorCI || store.buscandoCliente"
          @click="buscar">
          {{ store.buscandoCliente ? 'Buscando…' : 'Buscar' }}
        </button>
      </div>
      <p v-if="tocado.CI && !ciInput" class="campo-error">Ingresa tu CI para continuar.</p>
      <p v-else-if="tocado.CI && errorCI" class="campo-error">La cédula debe tener entre 4 y 10 dígitos numéricos.</p>
    </div>

    <transition name="fade-bloque">
      <div v-if="busquedaRealizada && store.clienteEncontrado && !identidadConfirmada" class="confirmacion-identidad">
        <div class="confirmacion-icono">👤</div>
        <div class="confirmacion-contenido">
          <p class="confirmacion-titulo">¿Eres tú?</p>
          <ul class="confirmacion-datos">
            <li><span class="dato-label">Nombre</span><span class="dato-valor">{{ nombreMascarado }}</span></li>
            <li><span class="dato-label">Teléfono</span><span class="dato-valor">{{ telefonoMascarado }}</span></li>
            <li><span class="dato-label">Correo</span><span class="dato-valor">{{ correoMascarado }}</span></li>
          </ul>
          <div class="confirmacion-acciones">
            <button class="btn-confirmar" @click="confirmarIdentidad">Sí, son mis datos</button>
            <button class="btn-texto" @click="ingresarManual">No, ingresar manualmente</button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade-bloque">
      <p v-if="busquedaRealizada && !store.clienteEncontrado && !modoManual" class="aviso-info">
        No encontramos registros con esa CI. Completa tus datos abajo.
      </p>
    </transition>

    <transition name="fade-bloque">
      <div v-if="mostrarFormulario" class="grid-2">

        <div class="campo">
          <label>Nombre</label>
          <input
            v-model="store.cliente.Nombre1"
            type="text"
            placeholder="Ej: Juan"
            :class="['input-field', tocado.Nombre1 && errorNombre ? 'input-error' : '']"
            @blur="tocado.Nombre1 = true"
          />
          <p v-if="tocado.Nombre1 && !store.cliente.Nombre1" class="campo-error">Ingresa tu nombre.</p>
          <p v-else-if="tocado.Nombre1 && errorNombre" class="campo-error">El nombre solo puede contener letras.</p>
        </div>

        <div class="campo">
          <label>Apellido</label>
          <input
            v-model="store.cliente.Apellido1"
            type="text"
            placeholder="Ej: Mamani"
            :class="['input-field', tocado.Apellido1 && errorApellido ? 'input-error' : '']"
            @blur="tocado.Apellido1 = true"
          />
          <p v-if="tocado.Apellido1 && !store.cliente.Apellido1" class="campo-error">Ingresa tu apellido.</p>
          <p v-else-if="tocado.Apellido1 && errorApellido" class="campo-error">El apellido solo puede contener letras.</p>
        </div>

        <div class="campo">
          <label>Teléfono/Celular</label>
          <input
            v-model="store.cliente.Telefono"
            type="text"
            inputmode="numeric"
            placeholder="Ej: 71234567"
            maxlength="10"
            :class="['input-field', tocado.Telefono && errorTelefono ? 'input-error' : '']"
            @blur="tocado.Telefono = true"
          />
          <p v-if="tocado.Telefono && !store.cliente.Telefono" class="campo-error">Ingresa tu número de contacto.</p>
          <p v-else-if="tocado.Telefono && errorTelefono" class="campo-error">Ingresa un número válido (De 8 a 10 dígitos sin letras).</p>
        </div>

        <div class="campo">
          <label>Correo electrónico</label>
          <input
            v-model="store.cliente.Correo"
            type="email"
            placeholder="Ej: juan@correo.com"
            :class="['input-field', tocado.Correo && errorCorreo ? 'input-error' : '']"
            @blur="tocado.Correo = true"
          />
          <p v-if="tocado.Correo && !store.cliente.Correo" class="campo-error">Ingresa tu correo.</p>
          <p v-else-if="tocado.Correo && errorCorreo" class="campo-error">El formato debe ser válido. Ejemplo: ejemplo@gmail.com</p>
        </div>

      </div>
    </transition>

    <transition name="fade-bloque">
      <div v-if="identidadConfirmada" class="resumen-confirmado">
        <div class="resumen-check">✓</div>
        <div class="resumen-contenido">
          <p class="resumen-nombre">{{ nombreMascarado }}</p>
          <p class="resumen-meta">{{ correoMascarado }} · {{ telefonoMascarado }}</p>
        </div>
        <button class="btn-texto resumen-cambiar" @click="cambiarDatos">Cambiar</button>
      </div>
    </transition>

    <transition name="fade-bloque">
      <div v-if="intentoContinuar && !formularioValido" class="banner-error">
        <strong>Faltan datos obligatorios o inválidos:</strong>
        Por favor, revisa las alertas en rojo para poder continuar con la reserva.
      </div>
    </transition>

    <div class="acciones">
      <span></span>
      <button class="btn-primario" @click="siguiente">Continuar →</button>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()

// ── Estado local ──────────────────────────────────────────────────────────────
const ciInput           = ref(store.cliente.CI || '')
const busquedaRealizada = ref(false)
const identidadConfirmada = ref(false)
const modoManual       = ref(false)
const intentoContinuar = ref(false)

// Registro de qué campos fueron tocados (para validación en tiempo real)
const tocado = ref({
  CI:        false,
  Nombre1:   false,
  Apellido1: false,
  Telefono:  false,
  Correo:    false,
})

// ── Validaciones individuales con propiedades computadas ──────────────────────

// REGLA: CI entre 4 y 10 caracteres numéricos sin espacios
const errorCI = computed(() => {
  const ci = String(ciInput.value || '').trim()
  return ci.length > 0 && !/^\d{4,10}$/.test(ci)
})

// REGLA: Nombres solo letras (acepta espacios internos, acentos latinos, diéresis y Ñ)
const errorNombre = computed(() => {
  const nom = String(store.cliente.Nombre1 || '').trim()
  return nom.length > 0 && !/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/.test(nom)
})

// REGLA: Apellidos solo letras (acepta espacios internos, acentos latinos, diéresis y Ñ)
const errorApellido = computed(() => {
  const ape = String(store.cliente.Apellido1 || '').trim()
  return ape.length > 0 && !/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/.test(ape)
})

// REGLA: Teléfono estricto, de 8 a 10 caracteres compuestos puramente por dígitos numéricos
const errorTelefono = computed(() => {
  const t = String(store.cliente.Telefono || '').replace(/\s/g, '')
  return t.length > 0 && !/^\d{8,10}$/.test(t)
})

const errorCorreo = computed(() => {
  const c = store.cliente.Correo || ''
  return c.length > 0 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c)
})

// ── Mostrar formulario manual ─────────────────────────────────────────────────
const mostrarFormulario = computed(() =>
  (busquedaRealizada.value && !store.clienteEncontrado) || modoManual.value
)

// ── Validez global del formulario ─────────────────────────────────────────────
const formularioValido = computed(() => {
  if (!ciInput.value || errorCI.value) return false
  if (identidadConfirmada.value) return true  // datos recuperados y respaldados del servidor
  
  return (
    store.cliente.Nombre1   && !errorNombre.value &&
    store.cliente.Apellido1 && !errorApellido.value &&
    store.cliente.Telefono  && !errorTelefono.value &&
    store.cliente.Correo    && !errorCorreo.value
  )

})
function soloNumerosYGuion(e) {
  const permitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Home', 'End']
  if (permitidas.includes(e.key)) return
  if (!/^[0-9\-]$/.test(e.key)) e.preventDefault()
}
// ── Enmascarado (Para protección de scraping de datos en la UI) ───────────────
function mascararNombre(str = '') {
  if (!str) return '***'
  const visible = str.slice(0, 2)
  return visible + '*'.repeat(Math.max(str.length - 2, 2))
}

function mascararTelefono(tel = '') {
  const s = String(tel)
  if (s.length < 4) return '****'
  return s.slice(0, 2) + '****' + s.slice(-2)
}

function mascararCorreo(correo = '') {
  const [usuario, dominio] = correo.split('@')
  if (!dominio) return '***@***.***'
  const visibleUsuario = usuario.slice(0, 3)
  return visibleUsuario + '***@' + dominio
}

const nombreMascarado   = computed(() => `${mascararNombre(store.cliente.Nombre1)} ${mascararNombre(store.cliente.Apellido1)}`)
const telefonoMascarado = computed(() => mascararTelefono(store.cliente.Telefono))
const correoMascarado   = computed(() => mascararCorreo(store.cliente.Correo))

// ── Acciones del Flujo ────────────────────────────────────────────────────────
async function buscar() {
  tocado.value.CI = true
  if (!ciInput.value || errorCI.value) return
  await store.buscarClientePorCI(ciInput.value)
  busquedaRealizada.value = true
  identidadConfirmada.value = false
  modoManual.value = false
}

function resetearBusqueda() {
  busquedaRealizada.value = false
  identidadConfirmada.value = false
  modoManual.value = false
}

function confirmarIdentidad() {
  identidadConfirmada.value = true
  store.cliente.CI = ciInput.value
}

function ingresarManual() {
  store.cliente.Nombre1   = ''
  store.cliente.Apellido1 = ''
  store.cliente.Telefono  = ''
  store.cliente.Correo    = ''
  modoManual.value = true
  identidadConfirmada.value = false
}

function cambiarDatos() {
  identidadConfirmada.value = false
  modoManual.value = true
  // Vaciar los campos sensibles para no exponerlos al reabrir el formulario.
  store.cliente.Telefono = ''
  store.cliente.Correo = ''
  // Evita que salten los mensajes de error en rojo apenas se abre el formulario.
  tocado.value.Telefono = false
  tocado.value.Correo = false
}

function siguiente() {
  store.cliente.CI = ciInput.value
  intentoContinuar.value = true

  // Forzar el renderizado de errores inline marcando todos los inputs como tocados
  Object.keys(tocado.value).forEach(k => (tocado.value[k] = true))

  if (!formularioValido.value) return
  store.irAPaso(2)
}

// Limpieza de estados visuales del banner global tras la corrección del usuario
watch([() => store.cliente.Correo, () => store.cliente.Telefono, () => store.cliente.Nombre1, () => store.cliente.Apellido1, ciInput], () => {
  if (intentoContinuar.value && formularioValido.value) {
    intentoContinuar.value = false
  }
})
</script>

<style scoped>
/* Los estilos permanecen idénticos a tu hoja CSS scoped previa */
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub    { font-size: 0.85rem; color: #666; margin-bottom: 1.75rem; }
.campo { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1.25rem; }
.campo label { font-family: var(--font-vintage, serif); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.15em; color: #c9a84c; font-weight: 700; }
.input-field { border: 1px solid #d5d0c6; padding: 0.7rem 0.85rem; font-size: 0.9rem; background: #fff; transition: border-color 0.15s; outline: none; }
.input-field:focus  { border-color: #1a1a2e; }
.input-field.input-error { border-color: #c0392b; background: #fff8f8; }
.campo-error { font-size: 0.72rem; color: #c0392b; margin: 0; line-height: 1.4; }
.fila-ci { display: flex; gap: 0.6rem; }
.fila-ci .input-field { flex: 1; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.25rem; }
@media (max-width: 560px) { .grid-2 { grid-template-columns: 1fr; } }
.aviso-info { font-size: 0.78rem; color: #8a6d1a; margin-bottom: 1.25rem; }
.confirmacion-identidad { display: flex; gap: 1rem; align-items: flex-start; background: #f8f5ef; border: 1px solid #e8e4da; border-left: 4px solid #c9a84c; padding: 1.1rem 1.25rem; margin-bottom: 1.5rem; }
.confirmacion-icono { font-size: 1.4rem; line-height: 1; flex-shrink: 0; margin-top: 0.1rem; }
.confirmacion-titulo { font-family: var(--font-vintage, serif); font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #1a1a2e; margin: 0 0 0.6rem; }
.confirmacion-datos { list-style: none; padding: 0; margin: 0 0 1rem; display: flex; flex-direction: column; gap: 0.3rem; }
.confirmacion-datos li { display: flex; gap: 0.5rem; font-size: 0.82rem; }
.dato-label { color: #999; min-width: 60px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; }
.dato-valor { color: #1a1a2e; font-weight: 600; font-family: monospace; letter-spacing: 0.05em; }
.confirmacion-acciones { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
.btn-confirmar { font-family: var(--font-vintage, serif); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.12em; font-weight: 700; padding: 0.6rem 1.1rem; background: #1a1a2e; color: #F4F0E6; border: none; cursor: pointer; transition: background 0.2s; }
.btn-confirmar:hover { background: #c9a84c; color: #1a1a2e; }
.btn-texto { background: none; border: none; font-size: 0.75rem; color: #888; cursor: pointer; text-decoration: underline; padding: 0; }
.btn-texto:hover { color: #1a1a2e; }
.resumen-confirmado { display: flex; align-items: center; gap: 0.85rem; background: #f0faf3; border: 1px solid #a8d5b5; border-left: 4px solid #2f7a3e; padding: 0.9rem 1.1rem; margin-bottom: 1.5rem; }
.resumen-check { font-size: 1.1rem; color: #2f7a3e; font-weight: 800; flex-shrink: 0; }
.resumen-contenido { flex: 1; }
.resumen-nombre { font-weight: 700; font-size: 0.88rem; color: #1a1a2e; margin: 0 0 0.15rem; }
.resumen-meta { font-size: 0.75rem; color: #555; margin: 0; }
.resumen-cambiar { font-size: 0.72rem; }
.banner-error { background: #fde8e8; border: 1px solid #e0a0a0; border-left: 4px solid #c0392b; color: #7a1f1f; padding: 0.85rem 1.1rem; font-size: 0.82rem; line-height: 1.5; margin-bottom: 1.25rem; }
.banner-error strong { font-weight: 700; }
.acciones { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
.btn-primario, .btn-secundario { font-family: var(--font-vintage, serif); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 700; padding: 0.75rem 1.5rem; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primario { background: #1a1a2e; color: #F4F0E6; }
.btn-primario:hover { background: #c9a84c; color: #1a1a2e; }
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; white-space: nowrap; }
.btn-secundario:disabled { opacity: 0.4; cursor: not-allowed; }
.fade-bloque-enter-active, .fade-bloque-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.fade-bloque-enter-from, .fade-bloque-leave-to { opacity: 0; transform: translateY(-4px); }
</style>