<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioSemanal extends Model
{
    protected $table = 'HorariosSemanales';
    protected $primaryKey = 'IdHorarioSemanal';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero',
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

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'IdHorarioSemanal', 'IdHorarioSemanal');
    }
}
