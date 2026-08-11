<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Cinturon y tirantes: si APP_URL es https, todas las URLs se generan https.
        // TrustProxies deberia bastar, pero esto cubre el caso de un proxy mal configurado.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Con tres contenedores escribiendo en el mismo SQLite (web, scheduler y cola),
        // WAL evita los "database is locked" y el timeout da margen para esperar el turno.
        if (DB::connection()->getDriverName() === 'sqlite') {
            rescue(function () {
                DB::statement('PRAGMA journal_mode=WAL');
                DB::statement('PRAGMA busy_timeout=10000');
                DB::statement('PRAGMA synchronous=NORMAL');
            }, report: false);
        }
    }
}
