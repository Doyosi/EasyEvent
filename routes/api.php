<?php

use Doyosi\EasyEvent\Http\Controllers\Api\EasyEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('easy-event.routes.api.middleware', ['api']))
    ->prefix(config('easy-event.routes.api.prefix', 'api/easy-events'))
    ->name(config('easy-event.routes.api.name', 'easy-events.api.'))
    ->group(function () {
        // GET /api/easy-events
        Route::get('/', [EasyEventController::class, 'index'])->name('index');

        // GET /api/easy-events/{event}
        Route::get('/{event}', [EasyEventController::class, 'show'])->name('show');
    });
