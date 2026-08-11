<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\MessageComposer;
use App\Services\WahaService;
use Illuminate\Console\Command;

class SendWeeklyDigest extends Command
{
    protected $signature = 'reminders:digest {--days= : Horizonte en dias} {--dry-run}';

    protected $description = 'Manda el resumen de lo que viene en las proximas semanas';

    public function handle(MessageComposer $composer, WahaService $waha): int
    {
        $horizon = (int) ($this->option('days') ?: config('reminders.digest.horizon_days'));

        $events = Event::active()->get()
            ->filter(fn (Event $e) => $e->daysUntilNext() <= $horizon)
            ->sortBy(fn (Event $e) => $e->daysUntilNext())
            ->values();

        $message = $composer->digest($events, $horizon);

        if ($this->option('dry-run')) {
            $this->line($message);

            return self::SUCCESS;
        }

        $chatId = config('waha.default_chat_id');

        if (! $chatId) {
            $this->error('Falta WAHA_DEFAULT_CHAT_ID en el .env');

            return self::FAILURE;
        }

        $waha->sendText($chatId, $message);
        $this->info("Resumen enviado con {$events->count()} eventos.");

        return self::SUCCESS;
    }
}
