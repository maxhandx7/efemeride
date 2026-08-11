<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $tz = config('reminders.timezone');

        // El corazon del asunto. Cada 15 min mira si algo toca a esta hora.
        $schedule->command('reminders:dispatch')
            ->everyMinute()
            ->timezone($tz)
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/reminders.log'));

        // Resumen semanal de lo que viene
        if (config('reminders.digest.enabled')) {
            $schedule->command('reminders:digest')
                ->weeklyOn(
                    $this->dayNumber(config('reminders.digest.day')),
                    config('reminders.digest.at')
                )
                ->timezone($tz);
        }

        // Vigilar que el telefono no se haya desconectado sin avisar
        $schedule->command('waha:status --notify')
            ->hourly()
            ->timezone($tz);

        // Limpieza: los logs viejos no le sirven a nadie
        $schedule->call(function () {
            \App\Models\ReminderLog::where('created_at', '<', now()->subYear())->delete();
        })->monthly();

        $schedule->command('queue:prune-failed --hours=336')->weekly();
    }

    protected function dayNumber(string $day): int
    {
        return match (strtolower($day)) {
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6,
            default => 1,
        };
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
