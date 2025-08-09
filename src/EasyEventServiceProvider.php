<?php

namespace Doyosi\EasyEvent;

use Doyosi\EasyEvent\Console\InstallCommand;
use Doyosi\EasyEvent\Support\EasyEvent as EasyEventCore;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EasyEventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/easy-event.php', 'easy-event');

        // Bind core service for Facade
        $this->app->singleton('doyosi.easy-event', fn () => new EasyEventCore());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'easy-event');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/easy-event.php' => config_path('easy-event.php'),
            ], 'easy-event-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'easy-event-migrations');

            $this->commands([
                InstallCommand::class,
            ]);
        }

        // Auto-load routes if enabled
        if (config('easy-event.routes.web.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
        if (config('easy-event.routes.panel.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/panel.php');
        }
        if (config('easy-event.routes.api.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        // Route extender macros
        Route::macro('easyEvents', function (array $options = []) {
            $cfg = array_replace_recursive(config('easy-event.routes.web'), $options);
            Route::middleware($cfg['middleware'] ?? ['web'])
                ->prefix($cfg['prefix'] ?? 'events')
                ->name($cfg['name'] ?? 'easy-events.')
                ->group(__DIR__ . '/../routes/web.php');
        });

        Route::macro('easyEventsPanel', function (array $options = []) {
            $cfg = array_replace_recursive(config('easy-event.routes.panel'), $options);
            Route::middleware($cfg['middleware'] ?? ['web', 'auth'])
                ->prefix($cfg['prefix'] ?? 'panel/easy-events')
                ->name($cfg['name'] ?? 'panel.easy-events.')
                ->group(__DIR__ . '/../routes/panel.php');
        });

        \Route::macro('easyEventsApi', function (array $options = []) {
            $cfg = array_replace_recursive(config('easy-event.routes.api'), $options);
            \Route::middleware($cfg['middleware'] ?? ['api'])
                ->prefix($cfg['prefix'] ?? 'api/easy-events')
                ->name($cfg['name'] ?? 'easy-events.api.')
                ->group(__DIR__ . '/../routes/api.php');
        });
    }
}
