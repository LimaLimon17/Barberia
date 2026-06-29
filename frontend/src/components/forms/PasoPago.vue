<template>
  <section class="paso">
    <div v-if="estado === 'Pendiente'">
      <h2 class="paso-titulo">Paga tu anticipo</h2>
      <p class="paso-sub">Escanea el QR y paga el anticipo antes de que expire el tiempo.</p>

      <div class="resumen-cita">
        <div class="fila-resumen"><span>Barbero</span><strong>{{ store.barberoSeleccionado?.nombre }}</strong></div>
        <div class="fila-resumen"><span>Fecha</span><strong>{{ store.fechaCita }} {{ formatearHoraMediodia(store.horaInicioSeleccionada) }}</strong></div>
        <div class="fila-resumen"><span>Duración total</span><strong>{{ store.duracionTotalMinutos }} min</strong></div>
        <div class="fila-resumen"><span>Costo total</span><strong>{{ Number(store.reservaActual?.CostoTotal).toFixed(0) }} Bs.</strong></div>
        <div class="fila-resumen destacado"><span>Anticipo a pagar (50%)</span><strong>{{ Number(store.reservaActual?.MontoAnticipo).toFixed(0) }} Bs.</strong></div>
      </div>

      <div class="qr-box">
        <img 
          :src="`https://quickchart.io/qr?text=${store.qrPago?.referencia || 'Generando...'}&size=200&centerImageUrl=https://img.icons8.com/ios-filled/50/c9a84c/scissors.png`" 
          alt="Código QR de Pago"
          class="qr-imagen"
        />
        <p class="qr-subtexto">QR · {{ store.qrPago?.referencia }}</p>
        <p class="qr-monto">{{ store.qrPago?.monto }} {{ store.qrPago?.moneda }}</p>
      </div>

      <div class="contador">
        Tiempo restante para pagar: <strong>{{ minutos }}:{{ segundos }}</strong>
      </div>

      <button class="btn-primario ancho" @click="onPagar">Ya pagué, confirmar pago</button>
    </div>

    <div v-else-if="estado === 'Confirmada'" class="resultado ok">
      <h2 class="paso-titulo">¡Cita confirmada!</h2>
      <p class="paso-sub">Tu reserva quedó confirmada. Recuerda: ya no podrás cancelar ni modificar esta cita, y el anticipo no es reembolsable.</p>
      
      <div class="resumen-cita">
        <div class="fila-resumen"><span>Barbero</span><strong>{{ store.barberoSeleccionado?.nombre }}</strong></div>
        <div class="fila-resumen"><span>Fecha</span><strong>{{ store.fechaCita }} {{ formatearHoraMediodia(store.horaInicioSeleccionada) }}</strong></div>
        <div class="fila-resumen"><span>Total</span><strong>{{ Number(store.reservaActual?.CostoTotal).toFixed(0) }} Bs.</strong></div>
      </div>
      
      <div class="d-flex flex-column gap-2">
        <button class="btn-primario ancho d-flex align-items-center justify-content-center gap-2" @click="descargarPDF">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
          </svg>
          Descargar Comprobante PDF
        </button>
        <button class="btn-secundario ancho" @click="store.reiniciar()">Hacer otra reserva</button>
      </div>

      <div style="display: none;">
        <div id="comprobante-pdf" class="ticket-pdf-layout">
          <div class="ticket-header">
            <h1 class="ticket-brand">THE LAMPLIGHT</h1>
            <p class="ticket-tagline">Barber Shop · La Paz</p>
            <div class="ticket-divider"></div>
            <h2 class="ticket-title">COMPROBANTE DE RESERVA</h2>
          </div>

          <div class="ticket-body">
            <div class="ticket-meta">
              <p><strong>Nro. Cita:</strong> #{{ store.reservaActual?.IdReserva || '000' }}</p>
              <p><strong>Fecha Emisión:</strong> {{ new Date().toLocaleDateString() }}</p>
            </div>
            
            <div class="ticket-table">
              <div class="ticket-row"><span>Maestro Barbero:</span><strong>{{ store.barberoSeleccionado?.nombre }}</strong></div>
              <div class="ticket-row"><span>Fecha Programada:</span><strong>{{ store.fechaCita }}</strong></div>
              <div class="ticket-row"><span>Horario de Inicio:</span><strong>{{ formatearHoraMediodia(store.horaInicioSeleccionada) }}</strong></div>
              <div class="ticket-row"><span>Duración Estimada:</span><strong>{{ store.duracionTotalMinutos }} minutos</strong></div>
              <div class="ticket-row"><span>Estado del Turno:</span><strong class="text-success">CONFIRMADA</strong></div>
            </div>

            <div class="ticket-totals">
              <div class="ticket-row-total"><span>Costo Total del Servicio:</span><strong>{{ Number(store.reservaActual?.CostoTotal).toFixed(0) }} Bs.</strong></div>
              <div class="ticket-row-total destacado"><span>Garantía Pagada (50%):</span><strong>{{ Number(store.reservaActual?.MontoAnticipo).toFixed(0) }} Bs.</strong></div>
              <div class="ticket-row-total"><span>Saldo Pendiente en Caja:</span><strong>{{ (Number(store.reservaActual?.CostoTotal) - Number(store.reservaActual?.MontoAnticipo)).toFixed(0) }} Bs.</strong></div>
            </div>
          </div>

          <div class="ticket-footer">
            <p>¡Gracias por tu confianza!</p>
            <small>Por política de la barbería, recuerda llegar con 5 minutos de anticipación. Las citas confirmadas no admiten devoluciones de anticipo.</small>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="estado === 'Expirada'" class="resultado error">
      <h2 class="paso-titulo">Tiempo Expirado</h2>
      <p class="paso-sub">No se registró el pago dentro de los 15 minutos. El horario fue liberado.</p>
      <button class="btn-primario ancho" @click="reintentar">Elegir otro horario</button>
    </div>

    <div v-else-if="estado === 'Invalidada'" class="resultado error">
      <h2 class="paso-titulo">Horario ya no disponible</h2>
      <p class="paso-sub">Otro cliente confirmó este horario antes que tú. Por favor elige un nuevo horario.</p>
      <button class="btn-primario ancho" @click="reintentar">Elegir otro horario</button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useReservaStore } from '../../stores/reserva.js'

const store = useReservaStore()

const estado = computed(() => store.reservaActual?.EstadoReserva || 'Pendiente')
const minutos = computed(() => {
  const segundosEnteros = Math.floor(store.segundosRestantes);
  return String(Math.floor(segundosEnteros / 60)).padStart(2, '0');
});

const segundos = computed(() => {
  const segundosEnteros = Math.floor(store.segundosRestantes);
  return String(segundosEnteros % 60).padStart(2, '0');
});

// FUNCIÓN PARA FORMATEAR LA HORA DINÁMICAMENTE (Evita el "AM/PM" duro)
function formatearHoraMediodia(horaString) {
  if (!horaString) return '';
  
  // Extrae horas y minutos (soporta formatos hh:mm:ss o hh:mm)
  const partes = horaString.split(':');
  let horas = parseInt(partes[0], 10);
  const minutos = partes[1];
  
  const sufijo = horas >= 12 ? 'PM' : 'AM';
  
  // Convierte al formato clásico de 12 horas
  horas = horas % 12;
  horas = horas ? horas : 12; // Si da 0, significa que son las 12
  
  // Retorna la cadena bien formateada (Ej: "02:30 PM")
  const horaCero = String(horas).padStart(2, '0');
  return `${horaCero}:${minutos} ${sufijo}`;
}

async function onPagar() {
  await store.pagarAnticipo()
}

function reintentar() {
  store.horaInicioSeleccionada = null
  store.reservaActual = null
  store.qrPago = null
  store.irAPaso(3)
  store.cargarSlotsDisponibles()
}

async function descargarPDF() {
  const html2pdf = (await import('html2pdf.js')).default;
  const elemento = document.getElementById('comprobante-pdf');

  const opciones = {
    margin:       [0.5, 0.5, 0.5, 0.5],
    filename:     `Comprobante-Reserva-${store.reservaActual?.IdReserva || 'Cita'}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true, logging: false },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };

  const clonNode = elemento.cloneNode(true);
  clonNode.style.display = 'block';

  html2pdf().set(opciones).from(clonNode).save();
}
</script>

<style scoped>
/* (Se mantienen exactamente los mismos estilos del paso anterior) */
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.5rem; }
.resumen-cita { border: 1px solid #e8e4da; margin-bottom: 1.5rem; }
.fila-resumen { display: flex; justify-content: space-between; padding: 0.7rem 1rem; border-bottom: 1px solid #f0ece2; font-size: 0.85rem; }
.fila-resumen:last-child { border-bottom: none; }
.fila-resumen.destacado { background: #f8f5ef; }
.fila-resumen span { color: #888; }
.qr-box { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }
.qr-imagen { width: 200px; height: 200px; border: 4px solid #1a1a2e; padding: 0.5rem; background: #fff; border-radius: 4px; }
.qr-subtexto { font-size: 0.75rem; color: #666; font-family: monospace; margin-top: 0.2rem; }
.qr-monto { font-weight: 800; color: #c9a84c; font-size: 1.1rem; }
.contador { text-align: center; margin-bottom: 1.5rem; font-size: 0.9rem; }
.resultado.ok .paso-titulo { color: #2f7a3e; }
.resultado.error .paso-titulo { color: #8a2222; }
.btn-primario, .btn-secundario { font-family: var(--font-vintage, serif); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 700; padding: 0.85rem 1.5rem; border: none; cursor: pointer; }
.btn-primario { background: #1a1a2e; color: #F4F0E6; }
.btn-primario:hover { background: #c9a84c; color: #1a1a2e; }
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; }
.ancho { width: 100%; text-align: center; }
.gap-2 { gap: 0.5rem; }

.ticket-pdf-layout { padding: 40px; background: #fdfcf9; color: #0d1f2d; font-family: 'Montserrat', sans-serif; }
.ticket-header { text-align: center; margin-bottom: 30px; }
.ticket-brand { font-family: 'Cinzel', serif; font-size: 2.2rem; font-weight: 800; letter-spacing: 2px; color: #0d1f2d; margin: 0; }
.ticket-tagline { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 3px; color: #9a8466; margin: 5px 0 0 0; }
.ticket-divider { height: 2px; background: #9a8466; width: 60px; margin: 15px auto; }
.ticket-title { font-size: 1.1rem; letter-spacing: 2px; font-weight: 700; color: #0d1f2d; margin: 0; }
.ticket-meta { display: flex; justify-content: space-between; font-size: 0.85rem; border-bottom: 1px solid #e5dfd3; padding-bottom: 10px; margin-bottom: 25px; }
.ticket-table, .ticket-totals { border: 1px solid #e5dfd3; background: #fff; margin-bottom: 20px; }
.ticket-row, .ticket-row-total { display: flex; justify-content: space-between; padding: 12px 15px; font-size: 0.9rem; border-bottom: 1px solid #e5dfd3; }
.ticket-row:last-child, .ticket-row-total:last-child { border-bottom: none; }
.ticket-row-total span { font-weight: 600; }
.ticket-row-total.destacado { background: #f4ece2; color: #0d1f2d; font-weight: 700; }
.text-success { color: #2f7a3e !important; }
.ticket-footer { text-align: center; margin-top: 40px; border-top: 1px dashed #9a8466; padding-top: 20px; }
.ticket-footer p { font-family: 'Cinzel', serif; font-weight: 700; font-size: 1rem; margin-bottom: 5px; }
.ticket-footer small { display: block; font-size: 0.72rem; color: #777; max-width: 500px; margin: 0 auto; line-height: 1.4; }
</style>