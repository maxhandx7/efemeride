<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsappReminder;
use App\Models\Event;
use App\Models\ReminderLog;
use App\Services\MessageComposer;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

class DispatchDueReminders extends Command
{
    protected $signature = 'reminders:dispatch
                            {--dry-run : Muestra lo que enviaria, sin enviar nada}
                            {--force : Ignora la hora configurada de cada evento}
                            {--date= : Simula otro dia (Y-m-d), util para probar}';

    protected $description = 'Revisa que recordatorios tocan hoy y los pone en cola';

    public function handle(MessageComposer $composer): int
    {
        $tz = config('reminders.timezone');
        $now = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'), $tz)
            : CarbonImmutable::now($tz);

        $today = $now->startOfDay();
        $queued = 0;
        $skipped = 0;

        $events = Event::active()->with('rules')->get();

        foreach ($events as $event) {
            foreach ($event->rules as $rule) {
                $target = $today->addDays($rule->days_before);
                $occurrence = $event->occurrenceInYear($target->year);

                if (! $occurrence->isSameDay($target)) {
                    continue;
                }

                // ¿Ya es la hora de molestar?
                if (! $this->option('force')) {
                    $sendAt = CarbonImmutable::parse(
                        $today->format('Y-m-d').' '.$event->send_at, $tz
                    );

                    if ($now->lt($sendAt)) {
                        $skipped++;

                        continue;
                    }
                }

                $message = $composer->forEvent($event, $rule->days_before, $occurrence);

                if ($this->option('dry-run')) {
                    $this->line("→ <fg=cyan>{$event->name}</> (D-{$rule->days_before})");
                    $this->line('  '.str_replace("\n", "\n  ", $message));
                    $queued++;

                    continue;
                }

                // La unique key de la tabla es la que garantiza que nadie repite mensaje.
                // Si dos procesos entran a la vez, uno de los dos recibe el rechazo de la BD.
                try {
                    $log = ReminderLog::firstOrCreate(
                        [
                            'event_id' => $event->id,
                            'days_before' => $rule->days_before,
                            'occurrence_date' => $occurrence->toDateString(),
                        ],
                        [
                            'status' => 'queued',
                            'chat_id' => $event->destinationChatId(),
                            'message' => $message,
                        ]
                    );
                } catch (UniqueConstraintViolationException) {
                    $skipped++;

                    continue;
                }

                if (! $log->wasRecentlyCreated) {
                    $skipped++;

                    continue;
                }

                SendWhatsappReminder::dispatch($log);
                $queued++;
            }
        }

        $verb = $this->option('dry-run') ? 'se enviarian' : 'en cola';
        $this->info("{$queued} recordatorios {$verb}, {$skipped} omitidos.");

        return self::SUCCESS;
    }
}
