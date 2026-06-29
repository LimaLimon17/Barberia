<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'Roles';
    protected $primaryKey = 'IdRol';
    public $timestamps = false;

    protected $fillable = [
        'Nombre', 'Descripcion', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'IdRol', 'IdRol');
    }
}
