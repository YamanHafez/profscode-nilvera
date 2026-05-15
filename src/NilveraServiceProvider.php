<?php

namespace ProfsCode\Nilvera;

use Illuminate\Support\ServiceProvider;

class NilveraServiceProvider extends ServiceProvider
{
    // Servisleri kaydet
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nilvera.php', 'nilvera');

        $this->app->singleton('nilvera', function ($app) {
            return new Nilvera(
                $app->make(\ProfsCode\Nilvera\Services\NilveraClient::class),
                $app->make(\ProfsCode\Nilvera\Services\InvoiceMapper::class)
            );
        });
    }

    // Servisleri başlat
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/nilvera.php' => config_path('nilvera.php'),
            ], 'nilvera-config');
        }
    }
}
