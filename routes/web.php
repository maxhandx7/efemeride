<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\WahaWebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/fechas');

// El candado se activa solo si defines PANEL_USER y PANEL_PASSWORD.
Route::middleware('auth.basic.simple')->group(function () {
    Route::get('/fechas', [EventController::class, 'index'])->name('events.index');
    Route::get('/fechas/nueva', [EventController::class, 'create'])->name('events.create');
    Route::post('/fechas', [EventController::class, 'store'])->name('events.store');
    Route::get('/fechas/{event}/editar', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/fechas/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/fechas/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/fechas/{event}/probar', [EventController::class, 'test'])->name('events.test');
});

// WAHA entra por aqui: sin CSRF (ver App\Http\Middleware\VerifyCsrfToken)
Route::post('/webhooks/waha', WahaWebhookController::class)->name('webhooks.waha');

Route::get('/salud', fn () => response()->json(['ok' => true, 'time' => now()->toIso8601String()]));
