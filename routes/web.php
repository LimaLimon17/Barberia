<?php

use App\Http\Controllers\BarberoDashboardController;
use Illuminate\Support\Facades\Route;

// Ruta directa y pública para tu módulo
Route::get('/', [BarberoDashboardController::class, 'index'])->name('barbero.dashboard');
Route::get('/barbero/dashboard', [BarberoDashboardController::class, 'index']);

// Acciones de los botones
Route::post('/barbero/reserva/{id}/ausente', [BarberoDashboardController::class, 'marcarAusente'])->name('barbero.ausente');
Route::post('/barbero/reserva/{id}/completar', [BarberoDashboardController::class, 'completarCita'])->name('barbero.completar');