<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Pago extends Model
{
    protected $table = 'Pagos';
    protected $primaryKey = 'IdPago';
    public $timestamps = false;
    protected $fillable = [
        'IdReserva',
        'IdVenta',
        'TipoPago',
        'Monto',
        'FechaPago',
        'MetodoPago',
        'EstadoPago',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];
    protected $casts = [
        'Monto' => 'decimal:2',
        'FechaPago' => 'datetime',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'IdVenta', 'IdVenta');
    }
}
