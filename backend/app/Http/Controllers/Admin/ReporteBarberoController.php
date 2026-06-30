<?php

namespace App\Http\Controllers\Barbero;

use App\Http\Controllers\Controller;
use App\Models\Barbero;
use App\Models\Comision;
use App\Models\Reserva;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteBarberoController extends Controller
{
    /**
     * HU-17 / RF26: Reportes personalizados del barbero.
     * Los montos de comisión se leen de la tabla Comisiones (misma fuente
     * que usa el barbero en su pantalla de "Comisiones"), no se recalculan
     * aquí para evitar números distintos entre pantallas.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $barbero = Barbero::where('IdUsuario', $usuario->IdUsuario)->first();

        if (!$barbero) {
            return response()->json(['message' => 'Barbero no encontrado'], 404);
        }

        $idBarbero = $barbero->IdBarbero;
        $filtro = $request->input('periodo', 'semana');

        if ($filtro === 'dia') {
            $inicio = Carbon::today();
            $fin = Carbon::today()->endOfDay();
        } else {
            $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        }

        $comisiones = Comision::where('IdBarbero', $idBarbero)
            ->whereBetween('Fecha', [$inicio, $fin])
            ->get();

        $idsReserva = $comisiones->pluck('IdReserva')->filter()->unique();
        $idsVenta = $comisiones->pluck('IdVenta')->filter()->unique();

        $reservas = Reserva::whereIn('IdReserva', $idsReserva)->with(['cliente', 'servicios'])->get()->keyBy('IdReserva');
        $ventas = Venta::whereIn('IdVenta', $idsVenta)->with(['cliente', 'detalles.producto'])->get()->keyBy('IdVenta');

        $detalleTransacciones = collect();
        $ingresosServicios = 0;
        $ingresosProductos = 0;
        $comisionServicios = 0;
        $comisionProductos = 0;
        $comisionAusentes = 0;

        foreach ($comisiones as $c) {
            $monto = (float) $c->MontoComision;

            if ($c->TipoComision === Comision::TIPO_SERVICIO && $c->IdReserva && $reservas->has($c->IdReserva)) {
                $reserva = $reservas[$c->IdReserva];
                $comisionServicios += $monto;
                $ingresosServicios += (float) $reserva->CostoTotal;

                $detalleTransacciones->push([
                    'Fecha' => Carbon::parse($reserva->FechaCita)->format('d/m/Y') . ' ' . substr($reserva->HoraInicio, 0, 5),
                    'Cliente' => trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? '')),
                    'Detalle' => 'Servicios: ' . $reserva->servicios->pluck('Nombre')->implode(', '),
                    'MontoTotal' => (float) $reserva->CostoTotal,
                    'Comision' => $monto,
                ]);
            }

            if ($c->TipoComision === Comision::TIPO_AUSENTE && $c->IdReserva && $reservas->has($c->IdReserva)) {
                $reserva = $reservas[$c->IdReserva];
                $comisionAusentes += $monto;

                $detalleTransacciones->push([
                    'Fecha' => Carbon::parse($reserva->FechaCita)->format('d/m/Y') . ' ' . substr($reserva->HoraInicio, 0, 5),
                    'Cliente' => trim(($reserva->cliente->Nombre1 ?? '') . ' ' . ($reserva->cliente->Apellido1 ?? '')),
                    'Detalle' => 'Cita Ausente (Anticipo)',
                    'MontoTotal' => (float) $reserva->MontoAnticipo,
                    'Comision' => $monto,
                ]);
            }

            if ($c->TipoComision === Comision::TIPO_PRODUCTO && $c->IdVenta && $ventas->has($c->IdVenta)) {
                $venta = $ventas[$c->IdVenta];
                $comisionProductos += $monto;
                $ingresosProductos += (float) $venta->MontoTotal;

                $prods = $venta->detalles->map(fn ($d) => ($d->producto->Nombre ?? '???') . ' (x' . $d->Cantidad . ')')->implode(', ');

                $detalleTransacciones->push([
                    'Fecha' => Carbon::parse($venta->Fecha)->format('d/m/Y H:i'),
                    'Cliente' => $venta->cliente ? trim($venta->cliente->Nombre1 . ' ' . $venta->cliente->Apellido1) : 'Consumidor Final',
                    'Detalle' => 'Productos: ' . $prods,
                    'MontoTotal' => (float) $venta->MontoTotal,
                    'Comision' => $monto,
                ]);
            }
        }

        $detalleTransacciones = $detalleTransacciones->sortBy('Fecha')->values();

        return response()->json([
            'periodo' => [
                'inicio' => $inicio->format('d/m/Y'),
                'fin' => $fin->format('d/m/Y'),
            ],
            'ingresos_totales' => round($ingresosServicios + $ingresosProductos, 2),
            'comision_calculada' => round($comisionServicios + $comisionProductos + $comisionAusentes, 2),
            'desglose_ganancias' => [
                'servicios' => round($comisionServicios, 2),
                'productos' => round($comisionProductos, 2),
                'ausentes' => round($comisionAusentes, 2),
            ],
            'detalle_transacciones' => $detalleTransacciones,
        ]);
    }
}