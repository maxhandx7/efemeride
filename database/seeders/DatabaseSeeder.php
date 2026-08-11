<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['name' => 'Mama', 'type' => 'birthday', 'day' => 14, 'month' => 3, 'year' => 1968, 'notes' => 'Le encantan las flores amarillas'],
            ['name' => 'Aniversario con Sara', 'type' => 'anniversary', 'day' => 2, 'month' => 9, 'year' => 2019],
            ['name' => 'Renovar dominio afdeveloper.com', 'type' => 'custom', 'day' => 21, 'month' => 11, 'year' => null],
        ];

        foreach ($samples as $data) {
            $event = Event::create($data + ['send_at' => '08:00:00']);

            foreach (config('reminders.default_days_before') as $days) {
                $event->rules()->create(['days_before' => $days]);
            }
        }
    }
}
