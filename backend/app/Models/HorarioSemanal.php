<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioSemanal extends Model
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
        'EstadoA' => 'boolean',
        'FechaA'  => 'datetime',
    ];

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'IdHorario', 'IdHorario');
    }
}