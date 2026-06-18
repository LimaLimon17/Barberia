<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionSemanal extends Model
{
    protected $table = 'ComisionesSemanales';
    protected $primaryKey = 'IdComision';
    public $timestamps = false;

    protected $fillable = [
        'IdBarbero',
        'Semana',
        'Año',
        'TotalServicios',
        'ComisionServicios',
        'TotalVentas',
        'ComisionVentas',
        'TotalAusentes',
        'ComisionAusentes',
        'ComisionTotal',
        'EstadoConsolidarSemana',
        'FechaConsolidado',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'TotalServicios' => 'decimal:2',
        'ComisionServicios' => 'decimal:2',
        'TotalVentas' => 'decimal:2',
        'ComisionVentas' => 'decimal:2',
        'ComisionTotal' => 'decimal:2',
        'EstadoConsolidarSemana' => 'boolean',
        'FechaConsolidado' => 'datetime',
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function barbero()
    {
        return $this->belongsTo(Barbero::class, 'IdBarbero', 'IdBarbero');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleComision::class, 'IdComision', 'IdComision');
    }
}
