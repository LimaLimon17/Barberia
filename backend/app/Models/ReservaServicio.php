<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaServicio extends Model
{
    protected $table = 'ReservaServicios';
    protected $primaryKey = 'IdReservaServicio';
    public $timestamps = false;

    protected $fillable = [
        'IdServicio',
        'IdReserva',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'IdServicio', 'IdServicio');
    }
}
