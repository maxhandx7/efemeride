<?php

namespace App\Jobs;

use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Enviar un mensaje suelto, sin tocar reminder_logs.
 * Para pruebas, respuestas del bot y cualquier cosa que no sea un recordatorio programado.
 */
class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(
        public string $chatId,
        public string $message,
    ) {}

    public function handle(WahaService $waha): void
    {
        $waha->sendText($this->chatId, $this->message);
    }
}
