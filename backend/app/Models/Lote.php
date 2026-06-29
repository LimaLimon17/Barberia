<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'Lotes';
    protected $primaryKey = 'IdLote';
    public $timestamps = false;

    protected $fillable = [
        'IdProducto', 'CantidadRecibida', 'CostoUnitario', 'FechaIngreso',
        'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $casts = [
        'CantidadRecibida' => 'integer',
        'CostoUnitario' => 'decimal:2',
        'FechaIngreso' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'IdProducto', 'IdProducto');
    }
}
