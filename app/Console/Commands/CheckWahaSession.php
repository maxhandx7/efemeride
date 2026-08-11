<?php

namespace App\Console\Commands;

use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckWahaSession extends Command
{
    protected $signature = 'waha:status {--notify : Escribe en el log si la sesion se cayo}';

    protected $description = 'Revisa si la sesion de WhatsApp sigue conectada';

    public function handle(WahaService $waha): int
    {
        $status = $waha->sessionStatus();
        $state = $status['status'] ?? 'DESCONOCIDO';

        if ($state === 'WORKING') {
            Cache::forget('waha.down_since');
            $this->info("Sesion '".config('waha.session')."': conectada.");

            return self::SUCCESS;
        }

        $this->error("Sesion '".config('waha.session')."': {$state}");

        if ($this->option('notify')) {
            $since = Cache::get('waha.down_since');

            if (! $since) {
                Cache::put('waha.down_since', now(), now()->addDay());
                Log::error("WAHA se desconecto (estado {$state}). Escanea el QR otra vez.");
            }
        }

        return self::FAILURE;
    }
}
