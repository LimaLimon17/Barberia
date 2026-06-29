<template>
  <div class="min-h-screen bg-gray-100 font-sans">
    
    <nav class="bg-gray-900 text-white shadow-md px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-3">
        <img src="/images/logo.jpeg" alt="Logo Barbería" class="h-9 w-auto object-contain rounded-md" />
        <span class="text-xl font-bold tracking-wider text-amber-400">DASHBOARD BARBERIA CONTROL</span>
        <span class="bg-gray-800 text-xs px-2.5 py-1 rounded-md text-gray-300 font-mono border border-gray-700">Módulo: Barbero</span>
      </div>
      <div class="flex items-center space-x-4 text-sm text-gray-300">
        <p>Sesión de Pruebas</p>
        <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
      </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto">
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-center">
          <h1 class="text-2xl font-black text-gray-800">¡Hola, Estefanía!</h1>
          <p class="text-gray-500 mt-1">Aquí tienes la gestión de tu agenda global e histórica y control financiero.</p>
        </div>
        
        
      </div>

      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full sm:w-1/2 relative">
          <input 
            v-model="search" 
            @input="doSearch"
            type="text" 
            placeholder="Buscar por Nombre, CI o Teléfono en toda la agenda..." 
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 pl-4 py-2.5 text-sm"
          />
        </div>
        
        <div class="flex items-center justify-end gap-3 w-full sm:w-auto">
          <button 
            @click="showModalReporte = true" 
            class="bg-blue-600 text-white font-bold text-sm px-4 py-2.5 rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2 w-full sm:w-auto justify-center"
          >
            📊 Ver Reporte de Comisiones
          </button>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
          <h2 class="font-bold text-gray-700">Agenda General de Citas</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-100 text-gray-600 font-bold text-xs uppercase tracking-wider border-b">
                <th class="p-4">Fecha Cita</th> <th class="p-4">Hora Cita</th>
                <th class="p-4">Cliente / Identificación</th>
                <th class="p-4">Servicios Solicitados</th>
                <th class="p-4">Monto Total</th>
                <th class="p-4">Anticipo</th>
                <th class="p-4">Estado Actual</th>
                <th class="p-4 text-center">Acciones Disponible</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cita in citas" :key="cita.IdReserva" class="border-b hover:bg-gray-50 transition">
                <td class="p-4 font-bold text-amber-700 text-sm">{{ cita.FechaCita }}</td> 
                <td class="p-4 font-mono font-bold text-gray-600 text-sm">{{ cita.HoraInicio }} - {{ cita.HoraFin }}</td>
                <td class="p-4">
                  <span class="block font-bold text-gray-800">{{ cita.Nombre1 }} {{ cita.Apellido1 }}</span>
                  <span class="text-xs text-gray-400 font-mono">CI: {{ cita.IdCliente }} | Tel: {{ cita.Telefono }}</span>
                </td>
                <td class="p-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="s in cita.servicios" :key="s" class="bg-amber-50 text-amber-700 border border-amber-200 text-xs px-2 py-0.5 rounded-md font-medium">
                      {{ s }}
                    </span>
                  </div>
                </td>
                <td class="p-4 font-bold text-gray-800">{{ cita.CostoTotal }} Bs.</td>
                <td class="p-4 text-emerald-600 font-semibold">{{ cita.MontoAnticipo }} Bs.</td>
                <td class="p-4">
                  <span :class="{
                    'bg-amber-100 text-amber-800 border border-amber-200': cita.EstadoReserva === 'Confirmada',
                    'bg-green-100 text-green-800 border border-green-200': cita.EstadoReserva === 'Completada',
                    'bg-red-100 text-red-800 border border-red-200': cita.EstadoReserva === 'Ausente'
                  }" class="px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide">
                    {{ cita.EstadoReserva }}
                  </span>
                </td>
                <td class="p-4 text-center">
                  <div v-if="cita.EstadoReserva === 'Confirmada'" class="flex justify-center gap-2">
                    <button @click="openModalCompletar(cita)" class="bg-amber-500 text-white font-bold text-xs px-3 py-2 rounded-lg hover:bg-amber-600 transition shadow-sm">
                      Completar
                    </button>
                    <button 
                      @click="marcarAusente(cita.IdReserva)" 
                      :disabled="!haPasadoTolerancia(cita.FechaCita, cita.HoraInicio)"
                      :class="haPasadoTolerancia(cita.FechaCita, cita.HoraInicio) ? 'bg-red-600 hover:bg-red-700 text-white cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                      class="font-bold text-xs px-3 py-2 rounded-lg transition shadow-sm"
                      :title="!haPasadoTolerancia(cita.FechaCita, cita.HoraInicio) ? 'Esperar los 5 minutos de tolerancia requeridos' : 'Marcar cliente como ausente'"
                    >
                      Ausente
                    </button>
                  </div>
                  <div v-else-if="cita.EstadoReserva === 'Completada'">
                    <button @click="imprimirSimulacionPDF(cita)" class="bg-gray-800 text-white font-bold text-xs px-3 py-2 rounded-lg hover:bg-gray-900 transition flex items-center gap-1 mx-auto">
                      🖨️ Nota de Venta
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="citas.length === 0">
                <td colspan="8" class="p-12 text-center text-gray-400 italic">No se encontraron citas en la agenda del barbero.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="showModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full overflow-hidden border">
          <div class="px-6 py-4 bg-gray-900 text-white flex justify-between items-center">
            <h2 class="text-lg font-bold">Cierre y Liquidación de Cita</h2>
            <button @click="showModal = false" class="text-gray-400 hover:text-white font-bold">✕</button>
          </div>
          
          <div class="p-6 max-h-[75vh] overflow-y-auto">
            <div v-if="!notaGenerada">
              <div v-if="estadoPaso === 'pregunta'" class="text-center py-6">
                <p class="text-xl font-extrabold text-gray-800 mb-6">¿Desea agregar productos del inventario a esta venta?</p>
                <div class="flex justify-center gap-4">
                  <button @click="avanzarAProductos(true)" class="px-6 py-3 bg-amber-500 text-white font-black rounded-xl hover:bg-amber-600 shadow-md transition flex items-center gap-2">
                    🛍️ Sí, Vender Productos
                  </button>
                  <button @click="avanzarAProductos(false)" class="px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition">
                    No, solo el servicio
                  </button>
                </div>
              </div>

              <div v-else>
                <div v-if="quiereProductos">
                  <h3 class="font-bold text-gray-800 border-b pb-1 mb-3 text-sm tracking-wide uppercase flex justify-between items-center">
                    <span>🛍️ Selección de Productos</span>
                    <button @click="estadoPaso = 'pregunta'" class="text-xs text-amber-600 font-bold hover:underline">← Cambiar Decisión</button>
                  </h3>
                  
                  <div class="space-y-2 mb-5">
                    <div v-for="prod in productos" :key="prod.IdProducto" class="flex justify-between items-center bg-gray-50 p-2.5 rounded-xl border border-gray-100 text-sm">
                      <div>
                        <span class="font-bold text-gray-800 block">{{ prod.Nombre }}</span> 
                        <span class="text-xs text-gray-400">Precio: {{ prod.PrecioVenta }} Bs. | Stock: {{ prod.StockActual }} u.</span>
                      </div>
                      <div class="flex items-center space-x-2">
                        <button type="button" @click="decrementarProducto(prod.IdProducto)" class="w-7 h-7 bg-gray-200 rounded-lg text-gray-700 font-black flex items-center justify-center hover:bg-gray-300">-</button>
                        <span class="w-8 text-center font-bold text-base text-gray-800">{{ carrito[prod.IdProducto] || 0 }}</span>
                        <button type="button" @click="incrementarProducto(prod.IdProducto, prod.StockActual)" class="w-7 h-7 bg-amber-500 text-white rounded-lg font-black flex items-center justify-center hover:bg-amber-600">+</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mb-5 bg-amber-50 border border-amber-200 p-4 rounded-xl text-sm text-gray-700 mt-4">
                  <p class="flex justify-between"><span>Costo Base del Servicio:</span> <strong>{{ selectedCita.CostoTotal }} Bs.</strong></p>
                  <p class="flex justify-between text-emerald-600 mt-1"><span>Anticipo Abonado (50%):</span> <strong>-{{ selectedCita.MontoAnticipo }} Bs.</strong></p>
                  <p v-if="calcularTotalProductos > 0" class="flex justify-between text-purple-700 font-semibold mt-1"><span>Productos Adicionales:</span> <strong>+{{ calcularTotalProductos }} Bs.</strong></p>
                  
                  <div class="border-t border-amber-200 my-2 pt-2 flex justify-between text-base font-bold text-gray-900">
                    <span>Total Líquido a Cobrar:</span> <span class="text-blue-600">{{ (selectedCita.CostoTotal - selectedCita.MontoAnticipo) + calcularTotalProductos }} Bs.</span>
                  </div>
                </div>

                <div class="mb-6">
                  <label class="block font-bold text-gray-700 text-sm mb-1">Método de Pago Utilizado</label>
                  <select v-model="metodoPago" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <option value="Efectivo">💵 Efectivo presencial</option>
                    <option value="QR">📱 Transferencia por Código QR</option>
                  </select>
                </div>

                <div class="flex justify-end gap-2 border-t pt-4">
                  <button @click="showModal = false" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-600 hover:bg-gray-200 font-medium transition">Cancelar</button>
                  <button @click="enviarCompletar" class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-bold shadow-md transition">Generar Nota de Venta Final</button>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-6">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-green-600 text-3xl">✓</span>
              </div>
              <h3 class="text-xl font-black text-gray-800">¡Cita Liquidada con Éxito!</h3>
              <p class="text-gray-500 text-sm mt-1 mb-6">El cobro ha sido procesado y el stock actualizado correctamente.</p>
              
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 max-w-sm mx-auto mb-6 text-sm text-gray-600">
                <strong>Cliente:</strong> {{ selectedCita.Nombre1 }} {{ selectedCita.Apellido1 }}<br>
                <strong>Monto Neto Pagado:</strong> {{ (selectedCita.CostoTotal - selectedCita.MontoAnticipo) + calcularTotalProductos }} Bs.
              </div>

              <div class="flex flex-col sm:flex-row justify-center gap-3">
                <button @click="imprimirDespuesDeGuardar" class="px-6 py-2.5 bg-gray-900 text-white text-sm font-black rounded-xl hover:bg-black shadow-md transition flex items-center justify-center gap-1">
                  🖨️ Imprimir Nota de Venta
                </button>
                <button @click="cerrarYRefrescarDashboard" class="px-6 py-2.5 bg-gray-200 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-300 transition">
                  Cerrar Ventana
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showModalReporte" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full overflow-hidden border">
          <div class="px-6 py-4 bg-blue-900 text-white flex justify-between items-center">
            <h2 class="text-lg font-bold flex items-center gap-2">📊 Resumen General de Comisiones</h2>
            <button @click="showModalReporte = false" class="text-gray-300 hover:text-white font-bold text-lg">✕</button>
          </div>
          
          <div class="p-6">
            <p class="text-sm text-gray-500 mb-4">
              Desglose detallado de las comisiones generadas de forma histórica por el barbero actualmente activo en el panel.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
              <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm text-center">
                <span class="block text-2xl mb-1">💇‍♂️</span>
                <span class="text-xs uppercase text-gray-500 font-bold tracking-wider block">Por Servicios</span>
                <span class="text-xl font-black text-amber-700 block mt-1">{{ reporteComisiones.ganancia_servicios }} Bs.</span>
                <span class="text-[10px] text-gray-400 font-medium block mt-0.5">(50% Comisión)</span>
              </div>

              <div class="bg-purple-50 border border-purple-200 p-4 rounded-xl shadow-sm text-center">
                <span class="block text-2xl mb-1">🛍️</span>
                <span class="text-xs uppercase text-gray-500 font-bold tracking-wider block">Por Productos</span>
                <span class="text-xl font-black text-purple-700 block mt-1">{{ reporteComisiones.ganancia_productos }} Bs.</span>
                <span class="text-[10px] text-gray-400 font-medium block mt-0.5">(10% de Venta)</span>
              </div>

              <div class="bg-red-50 border border-red-200 p-4 rounded-xl shadow-sm text-center">
                <span class="block text-2xl mb-1">⚠️</span>
                <span class="text-xs uppercase text-gray-500 font-bold tracking-wider block">Por Ausentes</span>
                <span class="text-xl font-black text-red-700 block mt-1">{{ reporteComisiones.ganancia_ausentes }} Bs.</span>
                <span class="text-[10px] text-gray-400 font-medium block mt-0.5">(50% Anticipo)</span>
              </div>
            </div>

            <div class="bg-gray-900 text-white rounded-xl p-5 flex flex-col sm:flex-row justify-between items-center gap-2 shadow-inner">
              <div>
                <h4 class="font-extrabold text-base tracking-wide text-amber-400">TOTAL NETO ACUMULADO</h4>
                <p class="text-xs text-gray-400">Sumatoria auditada de comisiones líquidas.</p>
              </div>
              <div class="text-3xl font-black text-emerald-400">
                {{ reporteComisiones.total_general }} <span class="text-sm font-normal text-white">Bs.</span>
              </div>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t">
              <button @click="showModalReporte = false" class="px-5 py-2 bg-gray-800 text-white rounded-xl hover:bg-gray-900 font-bold shadow-sm text-sm transition">
                Entendido, Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  citas: Array,
  comisionesSemanales: [Number, String],
  productos: Array,
  filters: Object,
  reporteComisiones: Object
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const showModalReporte = ref(false); 
const selectedCita = ref(null);
const metodoPago = ref('Efectivo');
const carrito = ref({});

const estadoPaso = ref('pregunta'); 
const quiereProductos = ref(false);
const notaGenerada = ref(false); 

const inicializarCarrito = () => {
  if (props.productos) {
    props.productos.forEach(p => {
      carrito.value[p.IdProducto] = 0;
    });
  }
};
inicializarCarrito();

const avanzarAProductos = (decision) => {
  quiereProductos.value = decision;
  if (!decision) {
    inicializarCarrito(); 
  }
  estadoPaso.value = 'formulario'; 
};

const calcularTotalProductos = computed(() => {
  let total = 0;
  if (!props.productos) return total;
  props.productos.forEach(p => {
    const cantidad = carrito.value[p.IdProducto] || 0;
    total += cantidad * parseFloat(p.PrecioVenta);
  });
  return total;
});

const incrementarProducto = (id, stockMax) => {
  if ((carrito.value[id] || 0) < stockMax) {
    carrito.value[id] = (carrito.value[id] || 0) + 1;
  }
};

const decrementarProducto = (id) => {
  if ((carrito.value[id] || 0) > 0) {
    carrito.value[id]--;
  }
};

const haPasadoTolerancia = (fechaCita, horaInicio) => {
  try {
    const [horas, minutos, segundos] = horaInicio.split(':');
    const citaDateTime = new Date(fechaCita);
    citaDateTime.setHours(parseInt(horas), parseInt(minutos) + 5, parseInt(segundos || 0));
    return new Date() >= citaDateTime;
  } catch (e) {
    return false;
  }
};

const doSearch = () => {
  const urlParams = new URLSearchParams(window.location.search);
  const barberoActual = urlParams.get('barbero') || '1';
  router.get('/', { 
    barbero: barberoActual,
    search: search.value 
  }, { preserveState: true, replace: true });
};

const marcarAusente = (id) => {
  if (confirm('¿Deseas marcar al cliente como Ausente? Se aplicará la retención automática del 50% del anticipo.')) {
    router.post(`/barbero/reserva/${id}/ausente`);
  }
};

const openModalCompletar = (cita) => {
  selectedCita.value = cita;
  estadoPaso.value = 'pregunta'; 
  quiereProductos.value = false;
  notaGenerada.value = false; 
  inicializarCarrito(); 
  showModal.value = true;
};

const enviarCompletar = () => {
  const productosFinales = [];
  Object.keys(carrito.value).forEach(id => {
    if (carrito.value[id] > 0) {
      productosFinales.push({
        idProducto: parseInt(id),
        cantidad: carrito.value[id]
      });
    }
  });

  router.post(`/barbero/reserva/${selectedCita.value.IdReserva}/completar`, {
    MetodoPago: metodoPago.value,
    productos: productosFinales
  }, {
    preserveScroll: true,
    onSuccess: () => {
      notaGenerada.value = true;
    }
  });
};

const imprimirDespuesDeGuardar = () => {
  let detalleProductos = "";
  let totalProductos = 0;

  if (props.productos) {
    props.productos.forEach(p => {
      const cantidad = carrito.value[p.IdProducto] || 0;
      if (cantidad > 0) {
        const subtotal = cantidad * parseFloat(p.PrecioVenta);
        totalProductos += subtotal;
        detalleProductos += `    - Productos vendidos: ${cantidad} - ${p.Nombre}\n`;
      }
    });
  }

  if (detalleProductos === "") {
    detalleProductos = `    - Productos vendidos: Ninguno\n`;
  }

  const saldoServicio = parseFloat(selectedCita.value.CostoTotal) - parseFloat(selectedCita.value.MontoAnticipo);
  const totalCaja = saldoServicio + totalProductos;

  let ticket = `
    =============================================
             NOTA DE VENTA - BARBERIA 
    =============================================
    Cita Nro: ${selectedCita.value.IdReserva}
    Cliente: ${selectedCita.value.Nombre1} ${selectedCita.value.Apellido1}
    CI Cliente: ${selectedCita.value.IdCliente}
    Fecha de Servicio: ${selectedCita.value.FechaCita}
    ---------------------------------------------
    DETALLE DE CARGOS:
${detalleProductos}    - Anticipo Previo (50% QR): -${parseFloat(selectedCita.value.MontoAnticipo).toFixed(2)} Bs.
    ---------------------------------------------
    TOTAL NETO A PAGAR EN CAJA: ${totalCaja} Bs.
    ---------------------------------------------
    SIMULACION DE FACTURA
    ¡Gracias por su preferencia, Vuelva pronto!
    =============================================
  `;

  const win = window.open("", "_blank");
  win.document.write(`<pre style="font-family: monospace; font-size: 14px; line-height: 1.5;">${ticket}</pre>`);
  win.document.close();
  win.print();
};

const cerrarYRefrescarDashboard = () => {
  showModal.value = false;
  const urlParams = new URLSearchParams(window.location.search);
  const barberoActual = urlParams.get('barbero') || '1';

  router.get('/', { 
    barbero: barberoActual,
    search: search.value 
  }, { 
    preserveScroll: true,
    replace: true 
  });
};

const imprimirSimulacionPDF = (cita) => {
  let detalleProductos = "";
  let totalProductos = 0;

  if (cita.productos_comprados && cita.productos_comprados.length > 0) {
    cita.productos_comprados.forEach(prod => {
      const subtotalProd = prod.Cantidad * parseFloat(prod.PrecioUnitario);
      totalProductos += subtotalProd;
      detalleProductos += `    - Productos vendidos: ${prod.Cantidad} - ${prod.NombreProducto}\n`;
    });
  } else {
    detalleProductos = `    - Productos vendidos: Ninguno\n`;
  }

  const saldoServicio = parseFloat(cita.CostoTotal) - parseFloat(cita.MontoAnticipo);
  const totalCaja = saldoServicio + totalProductos;

  let ticket = `
    =============================================
             NOTA DE VENTA - BARBERIA 
    =============================================
    Cita Nro: ${cita.IdReserva}
    Cliente: ${cita.Nombre1} ${cita.Apellido1}
    CI Cliente: ${cita.IdCliente}
    Fecha de Servicio: ${cita.FechaCita}
    ---------------------------------------------
    DETALLE DE CARGOS:
${detalleProductos}    - Anticipo Previo (50% QR): -${parseFloat(cita.MontoAnticipo).toFixed(2)} Bs.
    ---------------------------------------------
    TOTAL NETO A PAGAR EN CAJA: ${totalCaja} Bs.
    ---------------------------------------------
    SIMULACION DE FACTURA
    ¡Gracias por su preferencia, Vuelva pronto!
    =============================================
  `;

  const win = window.open("", "_blank");
  win.document.write(`<pre style="font-family: monospace; font-size: 14px; line-height: 1.5;">${ticket}</pre>`);
  win.document.close();
  win.print();
};
</script>