<template>
  <div class="com-wrapper">

    <div class="com-header">
      <button class="btn-volver" @click="$emit('cerrar')">← Volver</button>
      <h2 class="com-titulo">Comisiones</h2>
    </div>

    <!-- Tabs de modo -->
    <div class="tabs-wrap">
      <button :class="['tab-btn', store.modo === 'semana' ? 'activo' : '']" @click="volverASemana">
        Semana actual / navegación
      </button>
      <button :class="['tab-btn', store.modo === 'personalizado' ? 'activo' : '']" @click="mostrarFiltro = true">
        Filtro personalizado
      </button>
    </div>

    <!-- Navegador semanal -->
    <div v-if="store.modo === 'semana'" class="com-navegador">
      <button class="btn-nav" @click="store.semanaAnterior" :disabled="store.cargando">← Semana anterior</button>
      <div class="com-rango">
        <strong>{{ formatearFecha(store.fechaInicio) }} – {{ formatearFecha(store.fechaFin) }}</strong>
        <span :class="['estado-tag', store.consolidado ? 'consolidado' : 'parcial']">
          {{ store.consolidado ? 'Consolidado' : 'Acumulado parcial' }}
        </span>
      </div>
      <button class="btn-nav" @click="store.semanaSiguiente" :disabled="store.cargando || esSemanaActual">Semana siguiente →</button>
    </div>

    <!-- Panel de filtro personalizado -->
    <div v-if="mostrarFiltro || store.modo === 'personalizado'" class="filtro-panel">
      <div class="filtro-grid">
        <div class="campo-grupo">
          <label class="campo-label">Desde</label>
          <input v-model="store.filtroDesde" type="date" class="campo-input" />
        </div>
        <div class="campo-grupo">
          <label class="campo-label">Hasta</label>
          <input v-model="store.filtroHasta" type="date" class="campo-input" />
        </div>
        <div class="campo-grupo">
          <label class="campo-label">Cliente (nombre o CI)</label>
          <input v-model="store.filtroCliente" type="text" placeholder="Opcional" class="campo-input" />
        </div>
      </div>
      <p class="filtro-ayuda">Tip: pon la misma fecha en "Desde" y "Hasta" para ver un solo día.</p>
      <div class="filtro-acciones">
        <button class="btn-buscar" :disabled="!store.filtroDesde || !store.filtroHasta || store.cargando" @click="aplicarFiltro">
          {{ store.cargando ? 'Buscando…' : 'Aplicar filtro' }}
        </button>
      </div>
    </div>

    <p v-if="store.modo === 'personalizado'" class="periodo-activo">
      Mostrando: {{ formatearFecha(store.fechaInicio) }} – {{ formatearFecha(store.fechaFin) }}
      <span v-if="store.clienteFiltro"> · Cliente: "{{ store.clienteFiltro }}"</span>
    </p>

    <p v-if="store.error" class="aviso-error">{{ store.error }}</p>
    <div v-if="store.cargando" class="loading-msg">Cargando comisiones…</div>

    <template v-else>
      <div class="com-totales">
        <div class="total-item"><span>Servicios (50%)</span><strong>{{ store.totales.servicios.toFixed(0) }} Bs.</strong></div>
        <div class="total-item"><span>Productos</span><strong>{{ store.totales.productos.toFixed(0) }} Bs.</strong></div>
        <div class="total-item"><span>Ausentes (50% anticipo)</span><strong>{{ store.totales.ausentes.toFixed(0) }} Bs.</strong></div>
        <div class="total-item neto"><span>TOTAL NETO</span><strong>{{ store.totales.neto.toFixed(2) }} Bs.</strong></div>
      </div>

      <div v-if="store.bloques.length === 0" class="aviso-gris">
        No hay comisiones registradas en este período{{ store.clienteFiltro ? ' para ese cliente' : '' }}.
      </div>

      <div v-else class="bloques-lista">
        <div v-for="(b, i) in store.bloques" :key="i" class="bloque-card">
          <div class="bloque-cabecera">
            <div>
              <strong>{{ formatearFecha(b.fecha) }}</strong>
              <span v-if="b.hora" class="bloque-hora">{{ b.hora.slice(0, 5) }}</span>
            </div>
            <span class="bloque-tipo">{{ b.tipo === 'cita' ? 'Cita' : 'Venta directa' }}</span>
          </div>
          <p class="bloque-cliente">{{ b.cliente || 'Cliente sin nombre' }}</p>

          <div v-if="b.servicios.length" class="bloque-detalle">
            <span v-for="(s, j) in b.servicios" :key="'s'+j" class="chip">{{ s }}</span>
          </div>
          <div v-if="b.productos.length" class="bloque-detalle">
            <span v-for="(p, j) in b.productos" :key="'p'+j" class="chip">{{ p.nombre }} ×{{ p.cantidad }}</span>
          </div>

          <div class="bloque-montos">
            <span v-if="b.comision_servicio > 0">Servicio: {{ b.comision_servicio.toFixed(0) }} Bs.</span>
            <span v-if="b.comision_producto > 0">Productos: {{ b.comision_producto.toFixed(0) }} Bs.</span>
            <span v-if="b.comision_ausente > 0">Ausente: {{ b.comision_ausente.toFixed(0) }} Bs.</span>
            <strong>Total: {{ b.comision_total.toFixed(0) }} Bs.</strong>
          </div>
        </div>
      </div>

      <button class="btn-primario" style="margin-top: 1.5rem;" @click="descargarPDF">⬇ Exportar a PDF</button>
    </template>

    <!-- Layout oculto para impresión -->
    <div style="display:none;">
      <div ref="pdfRef" class="ticket-pdf-layout">
        <div class="ticket-header">
          <h1 class="ticket-brand">THE LAMPLIGHT</h1>
          <p class="ticket-tagline">Reporte de Comisiones</p>
          <div class="ticket-divider"></div>
        </div>
        <div class="ticket-meta">
          <p><strong>Período:</strong> {{ store.fechaInicio }} – {{ store.fechaFin }}</p>
          <p v-if="store.clienteFiltro"><strong>Cliente:</strong> {{ store.clienteFiltro }}</p>
          <p v-if="store.modo === 'semana'"><strong>Estado:</strong> {{ store.consolidado ? 'Consolidado' : 'Parcial' }}</p>
        </div>
        <div class="ticket-table" v-for="(b, i) in store.bloques" :key="'pdf'+i">
          <div class="ticket-row" style="font-weight:700;">
            <span>{{ formatearFecha(b.fecha) }} {{ b.hora ? b.hora.slice(0,5) : '' }} — {{ b.cliente || 'Sin nombre' }}</span>
            <span>{{ b.comision_total.toFixed(0) }} Bs.</span>
          </div>
          <div v-if="b.servicios.length" class="ticket-row"><span>Servicios: {{ b.servicios.join(', ') }}</span><span></span></div>
          <div v-if="b.productos.length" class="ticket-row"><span>Productos: {{ b.productos.map(p => `${p.nombre} x${p.cantidad}`).join(', ') }}</span><span></span></div>
        </div>
        <div class="ticket-totals">
          <div class="ticket-row-total"><span>Total servicios:</span><strong>{{ store.totales.servicios.toFixed(2) }} Bs.</strong></div>
          <div class="ticket-row-total"><span>Total productos:</span><strong>{{ store.totales.productos.toFixed(2) }} Bs.</strong></div>
          <div class="ticket-row-total"><span>Total ausentes:</span><strong>{{ store.totales.ausentes.toFixed(2) }} Bs.</strong></div>
          <div class="ticket-row-total destacado"><span>TOTAL NETO:</span><strong>{{ store.totales.neto.toFixed(2) }} Bs.</strong></div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useComisionStore } from '../../stores/comision.js'

defineEmits(['cerrar'])
const store = useComisionStore()
const pdfRef = ref(null)
const mostrarFiltro = ref(false)

onMounted(() => store.cargarSemana())

const esSemanaActual = computed(() => {
  const ahora = new Date()
  const y = ahora.getFullYear()
  const m = String(ahora.getMonth() + 1).padStart(2, '0')
  const d = String(ahora.getDate()).padStart(2, '0')
  const hoyLocal = `${y}-${m}-${d}`
  return store.fechaFin >= hoyLocal
})

function formatearFecha(fechaStr) {
  if (!fechaStr) return ''
  const [y, m, d] = fechaStr.split('-')
  return `${d}/${m}/${y}`
}

function aplicarFiltro() {
  store.cargarPersonalizado()
}

function volverASemana() {
  mostrarFiltro.value = false
  store.cargarSemana(store.semana, store.anio)
}

async function descargarPDF() {
  const html2pdf = (await import('html2pdf.js')).default
  const clon = pdfRef.value.cloneNode(true)
  clon.style.display = 'block'

  await html2pdf().set({
    margin: [0.5, 0.5, 0.5, 0.5],
    filename: `Comisiones-${store.fechaInicio}-${store.fechaFin}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, logging: false },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
  }).from(clon).save()
}
</script>

<style scoped>
.com-wrapper { max-width: 760px; margin: 0 auto; padding: 0 0 2rem; color: var(--color-text-primary); }
.com-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.btn-volver { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; }
.com-titulo { font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; margin: 0; }

.tabs-wrap { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.tab-btn { background: transparent; border: none; border-bottom: 2px solid transparent; color: var(--color-text-secondary); padding: 0.6rem 0.25rem; margin-right: 1.25rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; }
.tab-btn.activo { color: var(--color-gold); border-bottom-color: var(--color-gold); }

.com-navegador { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.btn-nav { background: transparent; border: 1px solid var(--color-border); color: var(--color-text-secondary); padding: 0.5rem 0.9rem; border-radius: 6px; font-size: 0.78rem; cursor: pointer; }
.btn-nav:disabled { opacity: 0.4; cursor: not-allowed; }
.com-rango { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; font-size: 0.85rem; }
.estado-tag { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.55rem; border-radius: 4px; }
.estado-tag.consolidado { background: rgba(22,163,74,0.15); color: #4ade80; }
.estado-tag.parcial { background: rgba(234,179,8,0.15); color: #facc15; }

.filtro-panel { border: 1px solid var(--color-border); border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 1.25rem; background: var(--color-bg-card); }
.filtro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.85rem; }
.campo-grupo { display: flex; flex-direction: column; gap: 0.35rem; }
.campo-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-secondary); font-weight: 600; }
.campo-input { background: var(--color-bg-input, rgba(255,255,255,0.05)); border: 1px solid var(--color-border); border-radius: 7px; padding: 0.55rem 0.75rem; font-size: 0.85rem; color: var(--color-text-primary); }
.filtro-ayuda { font-size: 0.72rem; color: var(--color-text-secondary); margin: 0.6rem 0 0; }
.filtro-acciones { display: flex; justify-content: flex-end; margin-top: 0.85rem; }
.btn-buscar { padding: 0.55rem 1.2rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
.btn-buscar:disabled { opacity: 0.5; cursor: not-allowed; }
.periodo-activo { font-size: 0.8rem; color: var(--color-text-secondary); margin-bottom: 1rem; }

.aviso-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; border-radius: 8px; padding: 0.7rem 1rem; font-size: 0.82rem; margin-bottom: 1.25rem; }
.aviso-gris { font-size: 0.82rem; color: var(--color-text-secondary); padding: 1.5rem 0; text-align: center; }
.loading-msg { text-align: center; color: var(--color-text-secondary); font-size: 0.85rem; padding: 2rem 0; }

.com-totales { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
.total-item { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 8px; padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.78rem; color: var(--color-text-secondary); }
.total-item strong { font-family: var(--font-heading); font-size: 1.1rem; color: var(--color-text-primary); }
.total-item.neto { border-color: var(--color-gold); }
.total-item.neto strong { color: var(--color-gold); }

.bloques-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.bloque-card { border: 1px solid var(--color-border); border-radius: 10px; padding: 0.9rem 1.1rem; background: var(--color-bg-card); }
.bloque-cabecera { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.bloque-hora { margin-left: 0.4rem; color: var(--color-text-secondary); font-size: 0.8rem; }
.bloque-tipo { font-size: 0.65rem; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: 4px; background: rgba(255,255,255,0.06); color: var(--color-text-secondary); }
.bloque-cliente { font-size: 0.85rem; font-weight: 600; margin: 0 0 0.5rem; }
.bloque-detalle { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.5rem; }
.chip { font-size: 0.7rem; padding: 0.2rem 0.55rem; background: rgba(255,255,255,0.05); border: 1px solid var(--color-border); border-radius: 20px; color: var(--color-text-secondary); }
.bloque-montos { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.78rem; color: var(--color-text-secondary); padding-top: 0.5rem; border-top: 1px dashed var(--color-border); }
.bloque-montos strong { color: var(--color-gold); }

.btn-primario { padding: 0.7rem 1.5rem; background: var(--color-gold); color: #1a1a2e; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; }

.ticket-pdf-layout { padding: 40px; background: #fdfcf9; color: #0d1f2d; font-family: 'Montserrat', sans-serif; }
.ticket-header { text-align: center; margin-bottom: 20px; }
.ticket-brand { font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 800; margin: 0; }
.ticket-tagline { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 2px; color: #9a8466; margin: 5px 0 0; }
.ticket-divider { height: 2px; background: #9a8466; width: 60px; margin: 15px auto; }
.ticket-meta { display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; border-bottom: 1px solid #e5dfd3; padding-bottom: 10px; margin-bottom: 20px; }
.ticket-table { border: 1px solid #e5dfd3; background: #fff; margin-bottom: 10px; }
.ticket-row, .ticket-row-total { display: flex; justify-content: space-between; padding: 8px 12px; font-size: 0.8rem; border-bottom: 1px solid #e5dfd3; }
.ticket-row:last-child { border-bottom: none; }
.ticket-totals { border: 1px solid #e5dfd3; background: #fff; margin-top: 16px; }
.ticket-row-total.destacado { background: #f4ece2; font-weight: 700; }
</style>