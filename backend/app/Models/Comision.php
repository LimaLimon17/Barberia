<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'Comisiones';
    protected $primaryKey = 'IdComision';
    public $timestamps = false;

    // Códigos de TipoComision (CHAR(3)):
    // 'SER' = comisión por servicio (50%) — se usará en Fase 3 (pago final)
    // 'PRO' = comisión por venta de producto — se usará en Fase 2
    // 'AUS' = retención por cliente ausente (50% del anticipo) — esta fase
    public const TIPO_SERVICIO = 'SER';
    public const TIPO_PRODUCTO = 'PRO';
    public const TIPO_AUSENTE  = 'AUS';

    protected $fillable = [
        'IdBarbero',
        'IdReserva',
        'IdVenta',
        'TipoComision',
        'Fecha',
        'MontoBase',
        'Porcentaje',
        'MontoComision',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'MontoBase' => 'decimal:2',
        'Porcentaje' => 'decimal:2',
        'MontoComision' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }
}