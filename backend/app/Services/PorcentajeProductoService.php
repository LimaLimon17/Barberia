<?php

namespace App\Services;

use App\Models\HistorialPorcentajeProducto;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PorcentajeProductoService
{
    public function crearHistorialInicial(Producto $producto, ?int $usuarioId = null): HistorialPorcentajeProducto
    {
        return HistorialPorcentajeProducto::create([
            'IdProducto' => $producto->IdProducto,
            'PorcentajeVenta' => $producto->PorcentajeVenta,
            'PorcentajeBarbero' => $producto->PorcentajeBarbero,
            'PrecioVenta' => $producto->PrecioVenta,
            'FechaInicio' => Carbon::today(),
            'FechaFin' => null,
            'EstadoA' => 1,
            'FechaA' => Carbon::now(),
            'UsuarioA' => $usuarioId,
        ]);
    }

    public function actualizarPorcentajes(Producto $producto, array $data): Producto
    {
        return DB::transaction(function () use ($producto, $data) {
            $usuarioId = $data['UsuarioA'] ?? null;
            $fechaInicio = $data['FechaInicio'] ?? Carbon::today()->toDateString();
            $hoy = Carbon::parse($fechaInicio)->toDateString();

            HistorialPorcentajeProducto::where('IdProducto', $producto->IdProducto)
                ->whereNull('FechaFin')
                ->update([
                    'FechaFin' => $hoy,
                    'EstadoA' => 0,
                    'FechaA' => Carbon::now(),
                    'UsuarioA' => $usuarioId,
                ]);

            $producto->fill([
                'PorcentajeVenta' => $data['PorcentajeVenta'],
                'PorcentajeBarbero' => $data['PorcentajeBarbero'],
                'PrecioVenta' => $data['PrecioVenta'],
                'FechaA' => Carbon::now(),
                'UsuarioA' => $usuarioId,
            ]);
            $producto->save();

            HistorialPorcentajeProducto::create([
                'IdProducto' => $producto->IdProducto,
                'PorcentajeVenta' => $data['PorcentajeVenta'],
                'PorcentajeBarbero' => $data['PorcentajeBarbero'],
                'PrecioVenta' => $data['PrecioVenta'],
                'FechaInicio' => $hoy,
                'FechaFin' => null,
                'EstadoA' => 1,
                'FechaA' => Carbon::now(),
                'UsuarioA' => $usuarioId,
            ]);

            return $producto->fresh(['historialActual']);
        });
    }
}
