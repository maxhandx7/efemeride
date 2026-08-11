<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Detras del proxy de Coolify, si APP_URL es https todas las URLs se generan https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Con tres contenedores escribiendo en el mismo SQLite (web, reloj y cola),
        // WAL evita los "database is locked" y el timeout da margen para esperar el turno.
        //
        // Va dentro de un listener a proposito: si tocamos la base directamente aqui,
        // Laravel abre la conexion en CADA arranque, incluido `package:discover` durante
        // el build de Docker, donde el archivo SQLite todavia no existe y todo revienta.
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() !== 'sqlite') {
                return;
            }

            rescue(function () use ($event) {
                $event->connection->statement('PRAGMA journal_mode=WAL');
                $event->connection->statement('PRAGMA busy_timeout=10000');
                $event->connection->statement('PRAGMA synchronous=NORMAL');
            }, report: false);
        });
    }
}
