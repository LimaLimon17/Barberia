<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Barbero\PerfilController;
use App\Http\Controllers\Admin\BarberoController;
use App\Http\Controllers\Admin\HorarioController;

use App\Http\Controllers\PublicController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Public\ReservaController;
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
// --- RUTAS PÚBLICAS (Catálogo y Flujo de Reservas del Cliente) ---
Route::get('/catalogo', [PublicController::class, 'obtenerCatalogoHome']);
Route::get('/cliente/buscar/{ci}', [PublicController::class, 'buscarClientePorCI']);
Route::post('/reservas/crear', [PublicController::class, 'crearReservaTentativa']);
Route::post('/reservas/procesar-pago', [PublicController::class, 'procesarValidacionPago']);

// Rutas públicas del flujo de reserva 

Route::prefix('clientes')->group(function () {
    Route::get('/{ci}', [ReservaController::class, 'buscarClientePorCI']);
});

Route::prefix('barberos')->group(function () {
    Route::get('/disponibilidad', [ReservaController::class, 'disponibilidadBarberos']);
});

Route::get('/servicios', [ReservaController::class, 'serviciosPorCategoria']);

Route::get('/disponibilidad/slots', [ReservaController::class, 'slotsDisponibles']);

Route::prefix('reservas')->group(function () {
    Route::post('/', [ReservaController::class, 'store']);
    Route::get('/{idReserva}/estado', [ReservaController::class, 'estado']);
    Route::post('/{idReserva}/confirmar-pago', [ReservaController::class, 'confirmarPago']);
});

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
});
});
