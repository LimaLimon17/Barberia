<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ServicioController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\PorcentajeProductoController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Barbero\VentaProductoController;

/*
|--------------------------------------------------------------------------
| Rutas API para módulo Catálogo, Inventario, Productos y Ventas
|--------------------------------------------------------------------------
| Copia estas rutas dentro de backend/routes/api.php.
*/

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'message' => 'API Barbería Laravel funcionando correctamente',
    ]);
});

Route::prefix('admin')->group(function () {
    Route::get('/categorias', [ServicioController::class, 'categorias']);
    Route::post('/categorias', [ServicioController::class, 'storeCategoria']);
    Route::put('/categorias/{id}', [ServicioController::class, 'updateCategoria']);
    Route::patch('/categorias/{id}/desactivar', [ServicioController::class, 'desactivarCategoria']);

    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::put('/servicios/{id}', [ServicioController::class, 'update']);
    Route::patch('/servicios/{id}/desactivar', [ServicioController::class, 'desactivar']);

    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::patch('/productos/{id}/desactivar', [ProductoController::class, 'desactivar']);
    Route::post('/productos/{id}/lotes', [ProductoController::class, 'registrarLote']);

    Route::get('/productos/{id}/porcentajes', [PorcentajeProductoController::class, 'historial']);
    Route::put('/productos/{id}/porcentajes', [PorcentajeProductoController::class, 'actualizar']);

    Route::get('/auditoria', [AuditoriaController::class, 'index']);
});

Route::prefix('barbero')->group(function () {
    Route::get('/ventas-productos/catalogo', [VentaProductoController::class, 'catalogo']);
    Route::post('/ventas-productos', [VentaProductoController::class, 'store']);
});
