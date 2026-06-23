<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Barbero\PerfilController;
use App\Http\Controllers\Admin\BarberoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\HorarioSemanalController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema Barbería
|--------------------------------------------------------------------------
|
| Rutas públicas: Login
| Rutas protegidas por rol: Barbero y Administrador
|
*/

// ==========================================
// RUTAS PÚBLICAS (sin autenticación)
// ==========================================
Route::post('/login', [LoginController::class, 'login']);

// ==========================================
// RUTAS PROTEGIDAS (requieren token Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);

    // ------------------------------------------
    // RUTAS DEL BARBERO (rol = 2)
    // ------------------------------------------
    Route::middleware('role:barbero')->prefix('barbero')->group(function () {
        Route::get('/perfil', [PerfilController::class, 'miPerfil']);
    });

    // ------------------------------------------
    // RUTAS DEL ADMINISTRADOR (rol = 1)
    // ------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->group(function () {
    // Barberos
    Route::get('/barberos',           [BarberoController::class, 'index']);
    Route::post('/barberos',          [BarberoController::class, 'store']);
    Route::get('/barberos/{id}',      [BarberoController::class, 'show']);
    Route::put('/barberos/{id}',      [BarberoController::class, 'update']);
    Route::delete('/barberos/{id}',   [BarberoController::class, 'destroy']);

    // Horarios
    Route::get('/barberos/{id}/horarios',  [HorarioController::class, 'index']);
    Route::post('/horarios',               [HorarioController::class, 'store']);

    // Horarios semanales (FIFO + rotación almuerzo)
    Route::get('/horarios-semana',              [HorarioSemanalController::class, 'index']);
    Route::post('/horarios-semana',             [HorarioSemanalController::class, 'store']);
    Route::put('/horarios-semana/{id}/descanso',[HorarioSemanalController::class, 'update']);
        
});
});
