<?php
namespace App\Services;

use App\Models\Barbero;
use App\Models\Comision;
use App\Models\Reserva;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComisionService
{
    /**
     * Calcula y consolida las comisiones de la semana actual para todos los barberos.
     * Esta función debe ser llamada los domingos a las 21:00.
     */
    public function consolidarSemana()
    {
        $hoy = Carbon::now();
        // Asumiendo que se corre el domingo a las 21:00, la semana inició el lunes pasado.
        $inicioSemana = $hoy->copy()->startOfWeek(Carbon::MONDAY)->setTime(0, 0, 0);
        $finSemana = $hoy->copy()->endOfWeek(Carbon::SUNDAY)->setTime(23, 59, 59);

        Log::info("Iniciando consolidación de comisiones del {$inicioSemana} al {$finSemana}");

        $barberos = Barbero::where('EstadoA', 1)->get();
        $totalComisionesGeneradas = 0;

        foreach ($barberos as $barbero) {
            DB::beginTransaction();
            try {
                // 1. Comisiones por Servicios (Reservas Completadas)
                $reservas = Reserva::where('IdBarbero', $barbero->IdBarbero)
                    ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
                    ->where('EstadoReserva', 'Completada')
                    ->get();

                foreach ($reservas as $reserva) {
                    $montoBase = $reserva->CostoTotal;
                    $montoComision = $montoBase * 0.50; // 50% según RF18

                    // Evitar duplicados
                    Comision::firstOrCreate([
                        'IdReserva' => $reserva->IdReserva,
                        'TipoComision' => 'SER'
                    ], [
                        'IdBarbero' => $barbero->IdBarbero,
                        'Fecha' => $hoy,
                        'MontoBase' => $montoBase,
                        'Porcentaje' => 50.00,
                        'MontoComision' => $montoComision,
                        'EstadoA' => 1,
                        'FechaA' => $hoy,
                        'UsuarioA' => 1 // Sistema
                    ]);
                    $totalComisionesGeneradas++;
                }

                // 2. Comisiones por Ausentes (50% del anticipo)
                $ausentes = Reserva::where('IdBarbero', $barbero->IdBarbero)
                    ->whereBetween('FechaCita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
                    ->where('EstadoReserva', 'Ausente')
                    ->where('MontoAnticipo', '>', 0)
                    ->get();

                foreach ($ausentes as $ausente) {
                    $montoComision = $ausente->MontoAnticipo * 0.50;
                    Comision::firstOrCreate([
                        'IdReserva' => $ausente->IdReserva,
                        'TipoComision' => 'AUS'
                    ], [
                        'IdBarbero' => $barbero->IdBarbero,
                        'Fecha' => $hoy,
                        'MontoBase' => $ausente->MontoAnticipo,
                        'Porcentaje' => 50.00,
                        'MontoComision' => $montoComision,
                        'EstadoA' => 1,
                        'FechaA' => $hoy,
                        'UsuarioA' => 1
                    ]);
                    $totalComisionesGeneradas++;
                }

                // 3. Comisiones por Productos (Ventas)
                $ventas = Venta::with('detalles.producto')
                    ->where('IdBarbero', $barbero->IdBarbero)
                    ->whereBetween('Fecha', [$inicioSemana, $finSemana])
                    ->where('EstadoA', 1)
                    ->get();

                foreach ($ventas as $venta) {
                    $montoBase = 0;
                    $montoComision = 0;
                    
                    foreach ($venta->detalles as $detalle) {
                        $montoBase += ($detalle->PrecioUnitario * $detalle->Cantidad);
                        $montoComision += $detalle->ComisionBarbero;
                    }

                    if ($montoComision > 0) {
                        Comision::firstOrCreate([
                            'IdVenta' => $venta->IdVenta,
                            'TipoComision' => 'PRO'
                        ], [
                            'IdBarbero' => $barbero->IdBarbero,
                            'Fecha' => $hoy,
                            'MontoBase' => $montoBase,
                            'Porcentaje' => null, // El porcentaje ya se aplicó línea por línea
                            'MontoComision' => $montoComision,
                            'EstadoA' => 1,
                            'FechaA' => $hoy,
                            'UsuarioA' => 1
                        ]);
                        $totalComisionesGeneradas++;
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error consolidando comisiones para barbero {$barbero->IdBarbero}: " . $e->getMessage());
            }
        }

        Log::info("Consolidación completada. {$totalComisionesGeneradas} comisiones registradas.");
        return $totalComisionesGeneradas;
    }
}
