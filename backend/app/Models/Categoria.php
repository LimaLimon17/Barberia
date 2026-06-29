<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'Categorias';
    protected $primaryKey = 'IdCategoria';
    public $timestamps = false;

    protected $fillable = [
        'Nombre', 'DuracionMinimaMinutos', 'DuracionMaximaMinutos',
        'PrecioMin', 'PrecioMax', 'EstadoA', 'FechaA', 'UsuarioA'
    ];

    protected $casts = [
        'PrecioMin' => 'decimal:2',
        'PrecioMax' => 'decimal:2',
    ];

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'IdCategoria', 'IdCategoria');
    }
}
