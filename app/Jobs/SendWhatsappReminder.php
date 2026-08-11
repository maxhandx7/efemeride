<?php

namespace App\Jobs;

use App\Models\ReminderLog;
use App\Services\MessageComposer;
use App\Services\WahaService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendWhatsappReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 60;

    /** Reintentos con paciencia: 1 min, 5 min, 15 min. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(public ReminderLog $log) {}

    public function handle(WahaService $waha, MessageComposer $composer): void
    {
        $log = $this->log->fresh();

        if (! $log || $log->status === 'sent') {
            return; // alguien ya lo mando, seguimos con nuestra vida
        }

        $event = $log->event;
        $chatId = $log->chat_id ?: $event->destinationChatId();

        if (! $chatId) {
            $log->markFailed('El evento no tiene destino y WAHA_DEFAULT_CHAT_ID esta vacio.');

            return;
        }

        $message = $log->message ?: $composer->forEvent(
            $event,
            $log->days_before,
            CarbonImmutable::parse($log->occurrence_date)
        );

        try {
            $messageId = $waha->sendText($chatId, $message);
            $log->markSent($messageId, $message);

            Log::info('Recordatorio enviado', [
                'event' => $event->name,
                'days_before' => $log->days_before,
            ]);
        } catch (RuntimeException $e) {
            $log->markFailed($e->getMessage());
            throw $e; // que la cola lo reintente
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->log->fresh()?->markFailed('Se agotaron los reintentos: '.$e->getMessage());
    }
}
