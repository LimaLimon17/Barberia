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
     * Relación con el rol del usuario.
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
     * Accessor: nombre completo del usuario.
     */
    public function getNombreCompletoAttribute()
    {
        $partes = array_filter([
            $this->Nombre1,
            $this->Nombre2,
            $this->Apellido1,
            $this->Apellido2,
        ]);
        return implode(' ', $partes);
    }
}
