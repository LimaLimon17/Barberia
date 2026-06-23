<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa, para una Semana/Año dada, cuál barbero tiene el almuerzo
 * tardío fijo (13:00-14:00) en lugar del almuerzo general (12:00-13:00),
 * y quién lo sustituye únicamente en el día que coincide con su descanso semanal.
 */
class TurnoAlmuerzoTardio extends Model
{
    protected $table = 'TurnoAlmuerzosTardios';
    protected $primaryKey = 'IdTurno';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero',
        'IdBarberoSustituto',
        'DiaSustituto',
        'Semana',
        'Año',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    public function barberoSustituto()
    {
        return $this->belongsTo(Barbero::class, 'IdBarberoSustituto', 'IdBarbero');
    }
}
