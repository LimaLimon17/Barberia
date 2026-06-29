<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComision extends Model
{
    protected $table = 'DetalleComisiones';
    protected $primaryKey = 'IdDetalle';
    public $timestamps = false;

    protected $fillable = [
        'IdComision',
        'IdReserva',
        'Fecha',
        'Monto',
        'Comision',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'Monto' => 'decimal:2',
        'Comision' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function comisionSemanal()
    {
        return $this->belongsTo(ComisionSemanal::class, 'IdComision', 'IdComision');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }
}

