<?php

use Doyosi\EasyEvent\Http\Controllers\Panel\EasyEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('easy-event.routes.panel.middleware', ['web', 'auth']))
    ->prefix(config('easy-event.routes.panel.prefix', 'panel/easy-events'))
    ->name(config('easy-event.routes.panel.name', 'panel.easy-events.'))
    ->group(function () {
        Route::get('/', [EasyEventController::class, 'index'])->name('index');
        Route::get('/create', [EasyEventController::class, 'create'])->name('create');
        Route::post('/', [EasyEventController::class, 'store'])->name('store');
        Route::get('/{event}/edit', [EasyEventController::class, 'edit'])->name('edit');
        Route::put('/{event}', [EasyEventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EasyEventController::class, 'destroy'])->name('destroy');
    });
