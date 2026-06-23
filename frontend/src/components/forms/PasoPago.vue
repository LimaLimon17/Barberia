<template>
  <section class="paso">
    <!-- Estado: Pendiente de pago -->
    <div v-if="estado === 'Pendiente'">
      <h2 class="paso-titulo">Paga tu anticipo</h2>
      <p class="paso-sub">Escanea el QR y paga el anticipo antes de que expire el tiempo.</p>

      <div class="resumen-cita">
        <div class="fila-resumen"><span>Barbero</span><strong>{{ store.barberoSeleccionado?.nombre }}</strong></div>
        <div class="fila-resumen"><span>Fecha</span><strong>{{ store.fechaCita }} {{ store.horaInicioSeleccionada }}</strong></div>
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

    <!-- Estado: Confirmada -->
    <div v-else-if="estado === 'Confirmada'" class="resultado ok">
      <h2 class="paso-titulo">¡Cita confirmada!</h2>
      <p class="paso-sub">Tu reserva quedó confirmada. Recuerda: ya no podrás cancelar ni modificar esta cita, y el anticipo no es reembolsable.</p>
      <div class="resumen-cita">
        <div class="fila-resumen"><span>Barbero</span><strong>{{ store.barberoSeleccionado?.nombre }}</strong></div>
        <div class="fila-resumen"><span>Fecha</span><strong>{{ store.fechaCita }} {{ store.horaInicioSeleccionada }}</strong></div>
        <div class="fila-resumen"><span>Total</span><strong>{{ Number(store.reservaActual?.CostoTotal).toFixed(0) }} Bs.</strong></div>
      </div>
      <button class="btn-secundario ancho" @click="store.reiniciar()">Hacer otra reserva</button>
    </div>

    <!-- Estado: Expirada -->
    <div v-else-if="estado === 'Expirada'" class="resultado error">
      <h2 class="paso-titulo">Tiempo Expirado</h2>
      <p class="paso-sub">No se registró el pago dentro de los 15 minutos. El horario fue liberado.</p>
      <button class="btn-primario ancho" @click="reintentar">Elegir otro horario</button>
    </div>

    <!-- Estado: Invalidada -->
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
</script>

<style scoped>
.paso { background: #fff; border: 1px solid #e8e4da; padding: 2rem; }
.paso-titulo { font-family: var(--font-vintage, serif); font-size: 1.4rem; font-weight: 800; margin-bottom: 0.4rem; }
.paso-sub { font-size: 0.85rem; color: #666; margin-bottom: 1.5rem; }

.resumen-cita { border: 1px solid #e8e4da; margin-bottom: 1.5rem; }
.fila-resumen {
  display: flex;
  justify-content: space-between;
  padding: 0.7rem 1rem;
  border-bottom: 1px solid #f0ece2;
  font-size: 0.85rem;
}
.fila-resumen:last-child { border-bottom: none; }
.fila-resumen.destacado { background: #f8f5ef; }
.fila-resumen span { color: #888; }

/* Busca y reemplaza o añade estas clases en tu sección de estilos */
.qr-box { 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  gap: 0.5rem; 
  margin-bottom: 1.5rem; 
}

.qr-imagen {
  width: 200px;
  height: 200px;
  border: 4px solid #1a1a2e; 
  padding: 0.5rem;
  background: #fff;
  border-radius: 4px;
}

.qr-subtexto {
  font-size: 0.75rem;
  color: #666;
  font-family: monospace;
  margin-top: 0.2rem;
}

.qr-monto { 
  font-weight: 800; 
  color: #c9a84c; 
  font-size: 1.1rem; 
}
.contador { text-align: center; margin-bottom: 1.5rem; font-size: 0.9rem; }

.resultado.ok .paso-titulo { color: #2f7a3e; }
.resultado.error .paso-titulo { color: #8a2222; }

.btn-primario, .btn-secundario {
  font-family: var(--font-vintage, serif);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-weight: 700;
  padding: 0.85rem 1.5rem;
  border: none;
  cursor: pointer;
}
.btn-primario { background: #1a1a2e; color: #F4F0E6; }
.btn-primario:hover { background: #c9a84c; color: #1a1a2e; }
.btn-secundario { background: transparent; border: 1px solid #1a1a2e; color: #1a1a2e; }
.ancho { width: 100%; text-align: center; }
</style>
