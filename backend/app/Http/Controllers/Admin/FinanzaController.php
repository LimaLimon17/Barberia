<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barbero;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\ComisionService;
use Carbon\Carbon;

class FinanzaController extends Controller
{
    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        $this->comisionService = $comisionService;
    }

    /**
     * Retorna el rendimiento financiero semanal y consolidado.
     */
    public function index(Request $request)
    {
        $fechaInicio = Carbon::now()->startOfWeek();
        $fechaActual = Carbon::now();

        $idBarbero = $request->query('id_barbero');

        // Consultas base
        $queryReservas = Reserva::whereBetween('FechaCita', [$fechaInicio->format('Y-m-d'), $fechaActual->format('Y-m-d')]);
        $queryVentas = Venta::with('detalles')->whereBetween('Fecha', [$fechaInicio->startOfDay(), $fechaActual->endOfDay()]);
        $queryBarberos = Barbero::with('usuario')->where('EstadoA', true);

        if ($idBarbero) {
            $queryReservas->where('IdBarbero', $idBarbero);
            $queryVentas->where('IdBarbero', $idBarbero);
            $queryBarberos->where('IdBarbero', $idBarbero);
        }

        $reservas = $queryReservas->get();
        $ventas = $queryVentas->get();
        $barberos = $queryBarberos->get();

        // 1. Ingresos por servicios
        $ingresosServicios = $reservas->where('EstadoReserva', 'Completada')->sum('CostoTotal');
        
        // 2. Ingresos por ventas
        $ingresosVentas = $ventas->sum('MontoTotal');

        // 3. Fondos de la barbería
        $fondoAusentes = $reservas->where('EstadoReserva', 'Ausente')->sum('MontoAnticipo') * 0.50; // La barbería se queda con el 50%
        
        $fondoProductos = 0;
        $totalComisionProductos = 0;
        foreach ($ventas as $venta) {
            $comisionBarbero = $venta->detalles->sum('ComisionBarbero');
            $totalComisionProductos += $comisionBarbero;
            $fondoProductos += ($venta->MontoTotal - $comisionBarbero);
        }
        
        $fondoServicios = $ingresosServicios * 0.50; // Barbería se queda 50%

        $fondosBarberia = [
            'servicios' => $fondoServicios,
            'productos' => $fondoProductos,
            'ausentes' => $fondoAusentes,
            'total' => $fondoServicios + $fondoProductos + $fondoAusentes
        ];

        // 4. Comisiones y desglose
        $comisionesAPagar = 0;
        $desgloseBarberos = [];

        foreach ($barberos as $barbero) {
            $ganancias = $this->comisionService->calcularGanancias($barbero, $fechaInicio, $fechaActual);
            $comisionesAPagar += $ganancias['total_ganado'];

            $desgloseBarberos[] = [
                'id' => $barbero->IdBarbero,
                'nombre' => $barbero->usuario->Nombre . ' ' . $barbero->usuario->Apellido,
                'servicios' => $ganancias['comision_servicios'],
                'productos' => $ganancias['comision_ventas'],
                'ausentes' => $ganancias['comision_ausentes'],
                'total' => $ganancias['total_ganado']
            ];
        }

        return response()->json([
            'periodo' => [
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaActual->format('Y-m-d')
            ],
            'ingresos_servicios' => $ingresosServicios,
            'ingresos_ventas' => $ingresosVentas,
            'ingresos_totales' => $ingresosServicios + $ingresosVentas,
            'fondos_barberia' => $fondosBarberia,
            'comisiones_a_pagar' => $comisionesAPagar,
            'desglose_barberos' => $desgloseBarberos
        ]);
    }
}
