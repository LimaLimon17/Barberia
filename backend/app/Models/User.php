<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'Usuarios';
    protected $primaryKey = 'IdUsuario';
    public $timestamps = false;

    protected $fillable = [
        'IdRol',
        'Nombre1',
        'Nombre2',
        'Apellido1',
        'Apellido2',
        'Correo',
        'Contraseña',
        'EstadoA',
        'FechaA',
        'UsuarioA',
    ];

    protected $hidden = [
        'Contraseña',
    ];

    protected $casts = [
        'EstadoA' => 'boolean',
        'FechaA' => 'datetime',
    ];

    /**
     * Nombre completo del usuario.
     */
    public function getNombreCompletoAttribute()
{
    return implode(' ', array_filter([
        $this->Nombre1,
        $this->Nombre2,
        $this->Apellido1,
        $this->Apellido2,
    ]));
}

    /**
     * Relación con el rol.
     */
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'IdRol', 'IdRol');
    }

    /**
     * Relación con el barbero (si el usuario es barbero).
     */
    public function barbero()
    {
        return $this->hasOne(Barbero::class, 'IdUsuario', 'IdUsuario');
    }

    /**
     * Verificar si el usuario es administrador.
     */
    public function esAdmin()
    {
        return $this->IdRol === 1;
    }

    /**
     * Verificar si el usuario es barbero.
     */
    public function esBarbero()
    {
        return $this->IdRol === 2;
    }

    /**
     * Overrides para Sanctum/Auth - usar campo Correo como identificador.
     */
    public function getAuthIdentifierName()
    {
        return 'IdUsuario';
    }

    /**
     * Override password field para autenticación.
     */
    public function getAuthPassword()
    {
        return $this->Contraseña;
    }
}
