<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Candado minimo para el panel: usuario y clave en el .env.
 * Sin tabla de usuarios, sin login, sin drama. Es una app de una sola persona.
 */
class SimpleBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = env('PANEL_USER');
        $pass = env('PANEL_PASSWORD');

        if (! $user || ! $pass) {
            return $next($request); // sin credenciales configuradas, pasa de largo
        }

        $given = [$request->getUser(), $request->getPassword()];

        if (hash_equals($user, (string) $given[0]) && hash_equals($pass, (string) $given[1])) {
            return $next($request);
        }

        return response('Necesitas la clave del panel.', 401, [
            'WWW-Authenticate' => 'Basic realm="Fechas"',
        ]);
    }
}
