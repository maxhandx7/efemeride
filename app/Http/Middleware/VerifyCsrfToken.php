<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /** WAHA no sabe de tokens CSRF; lo protegemos con el secreto del webhook. */
    protected $except = [
        'webhooks/waha',
    ];
}
