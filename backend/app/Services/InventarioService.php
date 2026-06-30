<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventarioService
{
    public function registrarEntrada(Producto $producto, array $data): Lote
    {
        return DB::transaction(function () use ($producto, $data) {
            $cantidad = (int) $data['CantidadRecibida'];
            $fechaIngreso = $data['FechaIngreso'] ?? Carbon::now();
            $usuarioId = $data['UsuarioA'] ?? null;

            $lote = Lote::create([
                'IdProducto' => $producto->IdProducto,
                'CantidadRecibida' => $cantidad,
                'CostoUnitario' => $data['CostoUnitario'],
                'FechaIngreso' => $fechaIngreso,
                'EstadoA' => 1,
                'FechaA' => Carbon::now(),
                'UsuarioA' => $usuarioId,
            ]);

            $producto->StockActual = ((int) $producto->StockActual) + $cantidad;
            $producto->FechaA = Carbon::now();
            $producto->UsuarioA = $usuarioId;
            $producto->save();

            return $lote;
        });
    }

    public function descontarStock(Producto $producto, int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        if ((int) $producto->StockActual < $cantidad) {
            throw new RuntimeException("Stock insuficiente para el producto {$producto->Nombre}.");
        }

        $producto->StockActual = ((int) $producto->StockActual) - $cantidad;
        $producto->FechaA = Carbon::now();
        $producto->save();
    }
}
