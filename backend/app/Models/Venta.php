<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'Ventas';
    protected $primaryKey = 'IdVenta';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero', 'IdCliente', 'IdReserva', 'Fecha', 'MontoTotal',
        'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'MontoTotal' => 'decimal:2',
    ];

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'IdCliente', 'CI');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'IdVenta', 'IdVenta');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'IdVenta', 'IdVenta');
    }

    public function comision()
    {
        return $this->hasOne(Comision::class, 'IdVenta', 'IdVenta');
    }
}
