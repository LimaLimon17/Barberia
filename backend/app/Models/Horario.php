<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'Horarios';
    protected $primaryKey = 'IdHorario';
    public $timestamps = false;

    protected $fillable = [
        'DiaSemana',
        'HoraEntrada',
        'HoraSalida',
        'DiaDescanso',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'DiaDescanso' => 'boolean',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function horariosBarberos()
    {
        return $this->hasMany(HorarioBarbero::class, 'IdHorario', 'IdHorario');
    }
}