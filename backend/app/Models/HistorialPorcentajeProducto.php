<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialPorcentajeProducto extends Model
{
    protected $table = 'HistorialPorcentajeProductos';
    protected $primaryKey = 'IdHistorial';
    public $timestamps = false;

    protected $fillable = [
        'IdProducto', 'PorcentajeVenta', 'PorcentajeBarbero', 'PrecioVenta',
        'FechaInicio', 'FechaFin', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $casts = [
        'PorcentajeVenta' => 'decimal:2',
        'PorcentajeBarbero' => 'decimal:2',
        'PrecioVenta' => 'decimal:2',
        'FechaInicio' => 'date',
        'FechaFin' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'IdProducto', 'IdProducto');
    }
}
