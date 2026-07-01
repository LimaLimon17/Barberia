<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Barbero\PerfilController;
use App\Http\Controllers\Admin\BarberoController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\HorarioSemanalController;


use App\Http\Controllers\PublicController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Public\ReservaController;

use App\Http\Controllers\Barbero\CitaPresencialController;
use App\Http\Controllers\Barbero\AgendaController;
use App\Http\Controllers\Barbero\VentaController;
use App\Http\Controllers\Barbero\PagoFinalController;
use App\Http\Controllers\Barbero\VentaDirectaController;
use App\Http\Controllers\Barbero\ComisionController;

use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\PorcentajeProductoController;
use App\Http\Controllers\Admin\ServicioController;

// Imports nuevos, junto a los demás use:
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
    Route::get('/categorias', [ReservaController::class, 'categorias']);
    Route::get('/{idReserva}/estado', [ReservaController::class, 'estado']);
    Route::post('/{idReserva}/confirmar-pago', [ReservaController::class, 'confirmarPago']);
});

// ==========================================
// RUTAS PROTEGIDAS (requieren token Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    // Dentro de Route::middleware('auth:sanctum')->group(...), al mismo nivel
// que el Logout, fuera de los sub-grupos de rol (la auditoría la usan ambos roles):
Route::post('/auditoria/reporte', [AuditoriaController::class, 'registrarReporte']);

    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);

    // ------------------------------------------
    // RUTAS DEL BARBERO (rol = 2)
    // ------------------------------------------
   Route::middleware('role:barbero')->prefix('barbero')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'miPerfil']);

    // RUTAS PARA CITA PRESENCIAL:
    Route::prefix('cita-presencial')->group(function () {
        Route::get('/inicializar',  [CitaPresencialController::class, 'inicializar']);
        Route::get('/servicios',    [CitaPresencialController::class, 'servicios']);
        Route::get('/slots',        [CitaPresencialController::class, 'slots']);
        Route::get('/citas',        [CitaPresencialController::class, 'misCitas']);
        Route::post('/crear',       [CitaPresencialController::class, 'crear']);
        Route::post('/{idReserva}/confirmar-pago', [CitaPresencialController::class, 'confirmarPago']);
        
    });

    // Búsqueda de cliente:
    Route::get('/cliente/{ci}', [ReservaController::class, 'buscarClientePorCI']);
   

Route::prefix('agenda')->group(function () {
    Route::get('/hoy', [AgendaController::class, 'citasHoy']);
    Route::get('/buscar', [AgendaController::class, 'buscarCitas']);
});
Route::put('/citas/{idReserva}/estado', [AgendaController::class, 'cambiarEstado']);



Route::get('/productos', [VentaController::class, 'productosDisponibles']);
Route::get('/citas/{idReserva}/venta', [VentaController::class, 'ventaDeLaCita']);
Route::post('/citas/{idReserva}/venta', [VentaController::class, 'agregarProductos']);

Route::prefix('citas/{idReserva}/pago-final')->group(function () {
    Route::get('/resumen', [PagoFinalController::class, 'resumen']);
    Route::post('/', [PagoFinalController::class, 'iniciar']);
    Route::post('/confirmar', [PagoFinalController::class, 'confirmar']);
});

Route::prefix('venta-directa')->group(function () {
    Route::post('/', [VentaDirectaController::class, 'iniciar']);
    Route::post('/confirmar', [VentaDirectaController::class, 'confirmar']);
});

Route::get('/comisiones', [ComisionController::class, 'semana']);
Route::get('/comisiones/filtrar', [ComisionController::class, 'filtrar']);

Route::put('/perfil/cambiar-password', [PerfilController::class, 'cambiarPassword']);
// Dentro de Route::middleware('role:barbero')->prefix('barbero')->group(...),
// junto a las rutas que ya tenemos (perfil, agenda, comisiones, etc.):
Route::get('/reportes', [ReporteBarberoController::class, 'index']);
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

    // Horarios semanales (FIFO)
    Route::get('/horarios-semana',              [HorarioSemanalController::class, 'index']);
    Route::post('/horarios-semana',             [HorarioSemanalController::class, 'store']);
    Route::put('/horarios-semana/{idBarbero}/descanso',[HorarioSemanalController::class, 'update']);

// Productos
Route::get('/productos', [ProductoController::class, 'index']);
Route::post('/productos', [ProductoController::class, 'store']);
Route::put('/productos/{id}', [ProductoController::class, 'update']);
Route::patch('/productos/{id}/desactivar', [ProductoController::class, 'desactivar']);
Route::post('/productos/{id}/lotes', [ProductoController::class, 'registrarLote']);

// Porcentajes de productos (historial)
Route::get('/productos/{id}/porcentajes', [PorcentajeProductoController::class, 'historial']);
Route::put('/productos/{id}/porcentajes', [PorcentajeProductoController::class, 'actualizar']);

// Categorías
Route::get('/categorias', [ServicioController::class, 'categorias']);
Route::post('/categorias', [ServicioController::class, 'storeCategoria']);
Route::put('/categorias/{id}', [ServicioController::class, 'updateCategoria']);
Route::patch('/categorias/{id}/desactivar', [ServicioController::class, 'desactivarCategoria']);

// Servicios
Route::get('/servicios', [ServicioController::class, 'index']);
Route::post('/servicios', [ServicioController::class, 'store']);
Route::put('/servicios/{id}', [ServicioController::class, 'update']);
Route::patch('/servicios/{id}/desactivar', [ServicioController::class, 'desactivar']);
        
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/finanzas', [FinanzaController::class, 'index']);
Route::get('/reportes/ventas', [ReporteController::class, 'ventas']);
Route::get('/reportes/inventario', [ReporteController::class, 'inventario']);
});
});
