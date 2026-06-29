<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barbero extends Model
{
    protected $table = 'Barberos';
    protected $primaryKey = 'IdBarbero';
    public $timestamps = false;

    protected $fillable = [
        'IdUsuario', 'FechaIngreso', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'IdUsuario', 'IdUsuario');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'IdBarbero', 'IdBarbero');
    }
}
