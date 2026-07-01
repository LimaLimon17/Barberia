#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'admin@barberia.com';
$password = 'admin123';

$user = User::updateOrCreate(
    ['Correo' => $email],
    [
        'IdRol' => 1,
        'Nombre1' => 'Admin',
        'Apellido1' => 'Sistema',
        'Correo' => $email,
        'Contraseña' => Hash::make($password),
        'EstadoA' => 1,
        'FechaA' => now(),
        'UsuarioA' => 1,
    ]
);

echo "Usuario administrador creado/actualizado: {$user->Correo}\n";
echo "Contraseña: {$password}\n";
echo "Hash almacenado: {$user->Contraseña}\n";
