<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'Productos';
    protected $primaryKey = 'IdProducto';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'CostoCompra',
        'PrecioVenta',
        'PorcentajeVenta',
        'PorcentajeBarbero',
        'StockActual',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'CostoCompra' => 'decimal:2',
        'PrecioVenta' => 'decimal:2',
        'PorcentajeVenta' => 'decimal:2',
        'PorcentajeBarbero' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    // Agregar dentro de la clase Producto, junto a $fillable/$casts existentes:

public function historialPorcentajes()
{
    return $this->hasMany(HistorialPorcentajeProducto::class, 'IdProducto', 'IdProducto');
}

/**
 * El período vigente: el registro de historial sin FechaFin (aún abierto).
 */
public function historialActual()
{
    return $this->hasOne(HistorialPorcentajeProducto::class, 'IdProducto', 'IdProducto')
        ->whereNull('FechaFin');
}
}