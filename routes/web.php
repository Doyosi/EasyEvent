<?php

use Doyosi\EasyEvent\Http\Controllers\Web\EasyEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('easy-event.routes.web.middleware', ['web']))
    ->prefix(config('easy-event.routes.web.prefix', 'events'))
    ->name(config('easy-event.routes.web.name', 'easy-events.'))
    ->group(function () {
        Route::get('/', [EasyEventController::class, 'index'])->name('index');
        Route::get('/{event}', [EasyEventController::class, 'show'])->name('show');
    });
