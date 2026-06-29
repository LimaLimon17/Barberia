<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Comision extends Model
{
    protected $table = 'Comisiones';
    protected $primaryKey = 'IdComision';
    public $timestamps = false;
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
    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'IdReserva', 'IdReserva');
    }
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'IdVenta', 'IdVenta');
    }
}
