<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'Reservas';
    protected $primaryKey = 'IdReserva';
    public $timestamps = false;

    protected $fillable = [
        'IdCliente', 'IdBarbero', 'FechaCita', 'HoraInicio', 'HoraFin',
        'CostoTotal', 'MontoAnticipo', 'FechaPagoAnticipo', 'MetodoPagoAnticipo',
        'EstadoReserva', 'HoraAusente', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'IdCliente', 'CI');
    }

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'ReservaServicios', 'IdReserva', 'IdServicio');
    }
}
