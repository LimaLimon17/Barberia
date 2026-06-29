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
        'StockActual',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'CostoCompra' => 'decimal:2',
        'PrecioVenta' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];
}
