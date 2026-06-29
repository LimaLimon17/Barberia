<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaGeneral extends Model
{
    protected $table = 'AuditoriaGeneral';
    protected $primaryKey = 'IdAuditoria';
    public $timestamps = false; // Usamos FechaA manualmente o NOW()

    protected $fillable = [
        'TablaNombre', 'RegistroId', 'Accion', 'Campo', 
        'ValorAnterior', 'ValorNuevo', 'UsuarioA', 
        'FechaA', 'DireccionIP', 'Detalles'
    ];
}