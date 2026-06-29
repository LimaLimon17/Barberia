<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Barbero\PerfilController;
use App\Http\Controllers\Admin\BarberoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanzaController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Barbero\ReporteBarberoController;
use App\Http\Controllers\AuditoriaController;
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
    // RUTAS GENERALES (Cualquier rol autenticado)
    // ------------------------------------------
    Route::post('/auditoria/reporte', [AuditoriaController::class, 'registrarReporte']);

    // ------------------------------------------
    // RUTAS DEL BARBERO (rol = 2)
    // ------------------------------------------
    Route::middleware('role:barbero')->prefix('barbero')->group(function () {
        Route::get('/perfil', [PerfilController::class, 'miPerfil']);
        Route::get('/reportes', [ReporteBarberoController::class, 'index']);
    });
    // ------------------------------------------
    // RUTAS DEL ADMINISTRADOR (rol = 1)
    // ------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/finanzas', [FinanzaController::class, 'index']);
        Route::get('/reportes/ventas', [ReporteController::class, 'ventas']);
        Route::get('/reportes/inventario', [ReporteController::class, 'inventario']);
        
        Route::get('/barberos', [BarberoController::class, 'index']);
        Route::get('/barberos/{id}', [BarberoController::class, 'show']);
        Route::put('/barberos/{id}', [BarberoController::class, 'update']);
    });
});
