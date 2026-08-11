<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class ImportEvents extends Command
{
    protected $signature = 'events:import {file : Ruta a un CSV con nombre,fecha,tipo,chat_id,notas}';

    protected $description = 'Importa fechas desde un CSV (fecha: Y-m-d o d/m)';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("No puedo leer {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $imported = 0;
        $failed = 0;

        while ($row = fgetcsv($handle)) {
            $data = array_combine($header, array_pad($row, count($header), null));

            [$day, $month, $year] = $this->parseDate($data['fecha'] ?? '');

            if (! $day) {
                $this->warn("Fecha invalida para: ".($data['nombre'] ?? '¿?'));
                $failed++;

                continue;
            }

            $event = Event::create([
                'name' => trim($data['nombre']),
                'type' => $data['tipo'] ?: 'birthday',
                'day' => $day,
                'month' => $month,
                'year' => $year,
                'chat_id' => $data['chat_id'] ?? null,
                'notes' => $data['notas'] ?? null,
                'send_at' => config('reminders.default_send_at').':00',
            ]);

            foreach (config('reminders.default_days_before') as $days) {
                $event->rules()->create(['days_before' => $days]);
            }

            $imported++;
        }

        fclose($handle);
        $this->info("{$imported} fechas importadas, {$failed} con problemas.");

        return self::SUCCESS;
    }

    protected function parseDate(string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return [(int) $m[3], (int) $m[2], (int) $m[1]];
        }

        if (preg_match('#^(\d{1,2})[/-](\d{1,2})$#', $raw, $m)) {
            return [(int) $m[1], (int) $m[2], null];
        }

        return [null, null, null];
    }
}
