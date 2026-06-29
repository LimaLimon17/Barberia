<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DetalleVenta extends Model
{
    protected $table = 'DetalleVenta';
    protected $primaryKey = 'IdDetalleVenta';
    public $timestamps = false;
    protected $fillable = [
        'IdVenta',
        'IdProducto',
        'Cantidad',
        'PrecioUnitario',
        'ComisionBarbero',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];
    protected $casts = [
        'PrecioUnitario' => 'decimal:2',
        'ComisionBarbero' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'IdVenta', 'IdVenta');
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'IdProducto', 'IdProducto');
    }
}
