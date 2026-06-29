<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'Reservas';
    protected $primaryKey = 'IdReserva';
    public $timestamps = false;

    protected $fillable = [
        'EstadoReserva', 'HoraAusente', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    public function servicios()
    {
        // Al poner la barra invertida '\' al inicio, Laravel busca el modelo desde la raíz y el editor ya no puede dar error
        return $this->belongsToMany(\App\Models\Servicio::class, 'reservaservicios', 'IdReserva', 'IdServicio');
    }
}