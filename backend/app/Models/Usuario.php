<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'Usuarios';
    protected $primaryKey = 'IdUsuario';
    public $timestamps = false;

    protected $fillable = [
        'IdRol', 'Nombre1', 'Nombre2', 'Apellido1', 'Apellido2',
        'Correo', 'Contraseña', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $hidden = ['Contraseña'];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'IdRol', 'IdRol');
    }

    public function barbero()
    {
        return $this->hasOne(Barbero::class, 'IdUsuario', 'IdUsuario');
    }
}
