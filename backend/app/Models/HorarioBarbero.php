<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioBarbero extends Model
{
    protected $table = 'HorariosBarberos';
    protected $primaryKey = 'IdHorarioBarbero';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero',
        'IdHorario',
        'FechaInicio',
        'FechaFin',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'FechaInicio' => 'date',
        'FechaFin' => 'date',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    /**
     * Relación con Barbero.
     */
    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    /**
     * Relación con Horario (la plantilla de horario).
     */
    public function horario()
    {
        return $this->belongsTo(Horario::class, 'IdHorario', 'IdHorario');
    }
}
