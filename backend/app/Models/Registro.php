<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Registro extends Model
{
    protected $table = 'Registros';
    protected $primaryKey = 'IdRegistro';
    public $timestamps = false;
    protected $fillable = [
        'IdBarbero',
        'Fecha',
        'HoraInicio',
        'HoraFin',
        'Observacion',
        'Ausencia',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];
    protected $casts = [
        'Fecha' => 'date',
        'Ausencia' => 'boolean',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];
    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }
}
