<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Barbero extends Model
{
    protected $table = 'Barberos';
    protected $primaryKey = 'IdBarbero';
    public $timestamps = false;

    protected $fillable = [
        'IdUsuario',
        'FechaIngreso',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'FechaIngreso' => 'date',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    /**
     * Relación con el usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'IdUsuario', 'IdUsuario');
    }

    /**
     * Relación con HorariosBarberos (asignaciones de horario).
     */
    public function horariosBarberos()
    {
        return $this->hasMany(HorarioBarbero::class, 'IdBarbero', 'IdBarbero');
    }

    /**
     * Relación con reservas.
     */
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'IdBarbero', 'IdBarbero');
    }

    /**
     * Relación con comisiones semanales.
     */
    public function comisionesSemanales()
    {
        return $this->hasMany(ComisionSemanal::class, 'IdBarbero', 'IdBarbero');
    }

    /**
     * Calcular antigüedad en días desde la fecha de ingreso.
     */
    public function getAntiguedadDiasAttribute()
    {
        if (!$this->FechaIngreso) {
            return 0;
        }
        return $this->FechaIngreso->diffInDays(Carbon::today());
    }

    /**
     * Obtener el estado como texto legible.
     */
    public function getEstadoTextoAttribute()
    {
        return $this->EstadoA ? 'Activo' : 'Inactivo';
    }
    public function horarios()
{
    // Relación de uno a muchos: Un barbero tiene muchos horarios semanales asignados
    return $this->hasMany(Horario::class, 'IdBarbero', 'IdBarbero');
}
}
