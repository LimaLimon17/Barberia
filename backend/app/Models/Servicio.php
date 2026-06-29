<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Servicio extends Model
{
    protected $table = 'Servicios';
    protected $primaryKey = 'IdServicio';
    public $timestamps = false;
    protected $fillable = [
        'IdCategoria',
        'Nombre',
        'FotoURL',
        'Precio',
        'DuracionMinutos',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];
    protected $casts = [
        'Precio' => 'decimal:2',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'IdCategoria', 'IdCategoria');
    }
}
