<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'Clientes';
    protected $primaryKey = 'CI';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'CI',
        'Nombre1',
        'Apellido1',
        'Telefono',
        'Correo',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $casts = [
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'IdCliente', 'CI');
    }
}
