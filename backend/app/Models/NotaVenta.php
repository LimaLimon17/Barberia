<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaVenta extends Model
{
    protected $table = 'NotaVentas';
    protected $primaryKey = 'IdNota';
    public $timestamps = false;

    protected $fillable = [
        'IdReserva',
        'FechaEmision',
        'MontoServicios',
        'MontoProductos',
        'MontoTotal',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'FechaEmision' => 'datetime',
        'MontoServicios' => 'decimal:2',
        'MontoProductos' => 'decimal:2',
        'MontoTotal' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }
}
