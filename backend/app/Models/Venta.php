<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'Ventas';
    protected $primaryKey = 'IdVenta';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero',
        'IdCliente',
        'IdReserva',
        'Fecha',
        'MontoTotal',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'MontoTotal' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'IdVenta', 'IdVenta');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }
    // Agregar dentro de la clase Venta, junto a detalles()/reserva()/barbero():

public function pagos()
{
    return $this->hasMany(Pago::class, 'IdVenta', 'IdVenta');
}

public function cliente()
{
    return $this->belongsTo(Cliente::class, 'IdCliente', 'CI');
}
}