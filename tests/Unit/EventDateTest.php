<?php

namespace Tests\Unit;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class EventDateTest extends TestCase
{
    /** Lo mas facil de romper en este tipo de app son las fechas. Aqui se prueban. */
    public function test_calcula_la_proxima_ocurrencia_del_anio_siguiente(): void
    {
        $event = new Event(['day' => 1, 'month' => 1]);
        $from = CarbonImmutable::create(2026, 6, 15, 0, 0, 0, config('reminders.timezone'));

        $this->assertSame('2027-01-01', $event->nextOccurrence($from)->toDateString());
    }

    public function test_el_mismo_dia_cuenta_como_hoy(): void
    {
        $event = new Event(['day' => 15, 'month' => 6]);
        $from = CarbonImmutable::create(2026, 6, 15, 0, 0, 0, config('reminders.timezone'));

        $this->assertSame(0, $event->daysUntilNext($from));
    }

    public function test_los_del_29_de_febrero_celebran_el_28(): void
    {
        $event = new Event(['day' => 29, 'month' => 2, 'year' => 2000]);

        $this->assertSame('2026-02-28', $event->occurrenceInYear(2026)->toDateString());
        $this->assertSame('2028-02-29', $event->occurrenceInYear(2028)->toDateString());
    }

    public function test_sin_anio_no_hay_edad(): void
    {
        $event = new Event(['day' => 10, 'month' => 10, 'year' => null, 'type' => 'birthday']);

        $this->assertNull($event->ageAtNextOccurrence());
    }
}
