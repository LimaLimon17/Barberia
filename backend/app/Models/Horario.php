<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'Horarios';
    protected $primaryKey = 'IdHorario';
    public $timestamps = false;

    protected $fillable = [
        'IdHorarioSemanal',
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

    public function horarioSemanal()
    {
        return $this->belongsTo(HorarioSemanal::class, 'IdHorarioSemanal', 'IdHorarioSemanal');
    }
}
