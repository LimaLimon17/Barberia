<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'Reservas';
    protected $primaryKey = 'IdReserva';
    public $timestamps = false;

    protected $fillable = [
        'IdCliente',
        'IdBarbero',
        'FechaCita',
        'HoraInicio',
        'HoraFin',
        'CostoTotal',
        'MontoAnticipo',
        'EstadoReserva',
        'FechaPagoAnticipo',
        'MetodoPagoFinal',
        'HoraAusente',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'FechaCita' => 'date',
        'CostoTotal' => 'decimal:2',
        'MontoAnticipo' => 'decimal:2',
        'FechaPagoAnticipo' => 'datetime',
        'HoraAusente' => 'datetime',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
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
        return $this->belongsToMany(
            Servicio::class,
            'ReservaServicios',
            'IdReserva',
            'IdServicio'
        );
    }

    public function notaVenta()
    {
        return $this->hasOne(NotaVenta::class, 'IdReserva', 'IdReserva');
    }
}
