<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AuditoriaGeneral;
use Inertia\Inertia;
use Carbon\Carbon;

class BarberoDashboardController extends Controller
{
    // HU-13 & HU-06: Panel Principal y Agenda Global de Clientes sin Restricción de Fecha
    public function index(Request $request)
    {
        // Truco de pruebas para cambiar usando ?barbero=1, 2 o 3
        $idBarbero = $request->query('barbero', 1); 
        
        // Filtro de búsqueda por texto (RF5)
        $search = $request->input('search');

        // 1. Obtener TODAS las citas filtradas por Barbero (de cualquier fecha)
        $citas = DB::table('Reservas as r')
            ->join('Clientes as c', 'r.IdCliente', '=', 'c.CI')
            ->select(
                'r.IdReserva',
                'r.FechaCita',
                'r.HoraInicio',
                'r.HoraFin',
                'r.CostoTotal',
                'r.MontoAnticipo',
                'r.EstadoReserva',
                'r.IdCliente',
                'c.Nombre1',
                'c.Apellido1',
                'c.Telefono'
            )
            ->where('r.IdBarbero', $idBarbero)
            ->when($search, function($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('c.Nombre1', 'like', "%{$search}%")
                      ->orWhere('c.Apellido1', 'like', "%{$search}%")
                      ->orWhere('c.CI', 'like', "%{$search}%")
                      ->orWhere('c.Telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('r.FechaCita', 'desc') // Ordenamos primero por la fecha más reciente
            ->orderBy('r.HoraInicio', 'asc')
            ->get();

        // Registrar los servicios de cada cita
        foreach ($citas as $cita) {
            $cita->servicios = DB::table('ReservaServicios as rs')
                ->join('Servicios as s', 'rs.IdServicio', '=', 's.IdServicio')
                ->where('rs.IdReserva', $cita->IdReserva)
                ->pluck('s.Nombre')
                ->toArray();
        }

        foreach ($citas as $cita) {
            $cita->productos_comprados = DB::table('ventas as v')
                ->join('detalleventa as dv', 'v.IdVenta', '=', 'dv.IdVenta')
                ->join('Productos as p', 'dv.IdProducto', '=', 'p.IdProducto')
                ->where('v.IdReserva', $cita->IdReserva)
                ->select(
                    'dv.Cantidad',
                    'dv.PrecioUnitario',
                    'p.Nombre as NombreProducto'
                )
                ->get()
                ->toArray();
        }

        // 2. Métricas Generales (Para la tarjeta superior)
        $comisionesSemanales = DB::table('Comisiones')
            ->where('IdBarbero', $idBarbero)
            ->sum('MontoComision');

        // ==========================================
        // REPORTE DE COMISIONES DETALLADO POR BARBERO
        // ==========================================
        
        // A. Ganancia por Servicios (50% de las citas completadas)
        $totalVentaProductosEnCitas = DB::table('reservas as r')
            ->join('ventas as v', 'r.IdReserva', '=', 'v.IdReserva')
            ->join('detalleventa as dv', 'v.IdVenta', '=', 'dv.IdVenta')
            ->where('r.IdBarbero', $idBarbero)
            ->where('r.EstadoReserva', 'Completada')
            ->sum(DB::raw('dv.Cantidad * dv.PrecioUnitario'));

        $costoTotalReservasCompletadas = DB::table('reservas')
            ->where('IdBarbero', $idBarbero)
            ->where('EstadoReserva', 'Completada')
            ->sum('CostoTotal');

        $baseServiciosPuros = $costoTotalReservasCompletadas - $totalVentaProductosEnCitas;
        $gananciaServicios = $baseServiciosPuros * 0.50;

        // B. Ganancia por Productos Vendidos (10% registrado en detalleventa)
        $gananciaProductos = DB::table('reservas as r')
            ->join('ventas as v', 'r.IdReserva', '=', 'v.IdReserva')
            ->join('detalleventa as dv', 'v.IdVenta', '=', 'dv.IdVenta')
            ->where('r.IdBarbero', $idBarbero)
            ->sum('dv.ComisionBarbero');

        // C. Ganancia por Penalizaciones "Ausente" (50% del MontoAnticipo retenido)
        $totalAnticiposAusentes = DB::table('reservas')
            ->where('IdBarbero', $idBarbero)
            ->where('EstadoReserva', 'Ausente')
            ->sum('MontoAnticipo');
        $gananciaAusentes = $totalAnticiposAusentes * 0.50;

        // Sumatoria Total del Reporte del Barbero Seleccionado
        $totalCalculadoReporte = $gananciaServicios + $gananciaProductos + $gananciaAusentes;

        $reporteComisiones = [
            'ganancia_servicios' => round($gananciaServicios, 2),
            'ganancia_productos' => round($gananciaProductos, 2),
            'ganancia_ausentes'  => round($gananciaAusentes, 2),
            'total_general'      => round($totalCalculadoReporte, 2)
        ];

        // 3. Catálogo de productos disponibles
        $productos = DB::table('Productos')
            ->where('StockActual', '>', 0)
            ->where('EstadoA', 1)
            ->get();

        // Enviamos la data necesaria quitando filtros de fecha obsoletos
        return Inertia::render('Barbero/Dashboard', [
            'citas' => $citas,
            'comisionesSemanales' => $comisionesSemanales ?? 0,
            'productos' => $productos,
            'filters' => $request->only(['search']),
            'reporteComisiones' => $reporteComisiones
        ]);
    }

    public function completarCita(Request $request, $idReserva)
    {
        $request->validate([
            'MetodoPago' => 'required|string',
            'productos' => 'array'
        ]);

        try {
            DB::beginTransaction();

            $reserva = DB::table('reservas')->where('IdReserva', $idReserva)->first();

            if (!$reserva) {
                return redirect()->back()->with('error', 'La reserva no existe.');
            }

            $idVenta = DB::table('ventas')->insertGetId([
                'IdReserva' => $idReserva,
                'IdCliente' => $reserva->IdCliente,
                'FechaA' => now(),
                'EstadoA' => 1,
                'UsuarioA' => 1
            ]);

            $totalProductos = 0;
            $productos = $request->input('productos', []);

            foreach ($productos as $item) {
                $productoDb = DB::table('productos')->where('IdProducto', $item['idProducto'])->first();

                if ($productoDb) {
                    if ($productoDb->StockActual < $item['cantidad']) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "Stock insuficiente para el producto: {$productoDb->Nombre}");
                    }
                    
                    $totalProductos += ($productoDb->PrecioVenta * $item['cantidad']);

                    DB::table('productos')
                        ->where('IdProducto', $item['idProducto'])
                        ->decrement('StockActual', $item['cantidad']);

                    DB::table('detalleventa')->insert([
                        'IdVenta' => $idVenta,
                        'IdProducto' => $item['idProducto'],
                        'Cantidad' => $item['cantidad'],
                        'PrecioUnitario' => $productoDb->PrecioVenta,
                        'ComisionBarbero' => ($productoDb->PrecioVenta * $item['cantidad']) * 0.10,
                        'EstadoA' => 1,
                        'FechaA' => now(),
                        'UsuarioA' => 1
                    ]);
                }
            }

            DB::table('reservas')
                ->where('IdReserva', $idReserva)
                ->update([
                    'EstadoReserva' => 'Completada',
                    'CostoTotal' => $reserva->CostoTotal + $totalProductos, 
                    'FechaA' => now()
                ]);

            DB::commit();
            return redirect()->back()->with('success', '¡Cita completada con éxito y stock actualizado!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al procesar el cierre: ' . $e->getMessage());
        }
    }
}