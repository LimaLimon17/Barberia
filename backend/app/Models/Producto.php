<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'Productos';
    protected $primaryKey = 'IdProducto';
    public $timestamps = false;

    protected $fillable = [
        'Nombre', 'CostoCompra', 'PrecioVenta', 'PorcentajeVenta',
        'PorcentajeBarbero', 'StockActual', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $casts = [
        'CostoCompra' => 'decimal:2',
        'PrecioVenta' => 'decimal:2',
        'PorcentajeVenta' => 'decimal:2',
        'PorcentajeBarbero' => 'decimal:2',
        'StockActual' => 'integer',
    ];

    public function lotes()
    {
        return $this->hasMany(Lote::class, 'IdProducto', 'IdProducto');
    }

    public function historialPorcentajes()
    {
        return $this->hasMany(HistorialPorcentajeProducto::class, 'IdProducto', 'IdProducto');
    }

    public function historialActual()
    {
        return $this->hasOne(HistorialPorcentajeProducto::class, 'IdProducto', 'IdProducto')
            ->whereNull('FechaFin')
            ->orderByDesc('FechaInicio')
            ->orderByDesc('IdHistorial');
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'IdProducto', 'IdProducto');
    }
}
