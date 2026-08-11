<?php

namespace App\Models;

use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'day', 'month', 'year', 'chat_id',
        'template', 'use_ai', 'send_at', 'notes', 'is_active',
    ];

    protected $casts = [
        'type' => EventType::class,
        'day' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'use_ai' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(ReminderRule::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * La proxima vez que toca celebrar, contando desde $from (o desde hoy).
     * Si la fecha ya paso este anio, salta al siguiente.
     */
    public function nextOccurrence(?CarbonImmutable $from = null): CarbonImmutable
    {
        $from = ($from ?? CarbonImmutable::now(config('reminders.timezone')))->startOfDay();

        $thisYear = $this->occurrenceInYear($from->year);

        return $thisYear->lt($from)
            ? $this->occurrenceInYear($from->year + 1)
            : $thisYear;
    }

    /**
     * La fecha del evento dentro de un anio concreto.
     * Los nacidos el 29 de febrero celebran el 28 cuando el anio no es bisiesto.
     */
    public function occurrenceInYear(int $year): CarbonImmutable
    {
        $tz = config('reminders.timezone');
        [$day, $month] = [$this->day, $this->month];

        $isLeapDay = $month === 2 && $day === 29;

        if ($isLeapDay && ! CarbonImmutable::create($year, 1, 1)->isLeapYear()) {
            [$day, $month] = config('reminders.leap_day_fallback') === 'march'
                ? [1, 3]
                : [28, 2];
        }

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, $tz);
    }

    public function daysUntilNext(?CarbonImmutable $from = null): int
    {
        $from = ($from ?? CarbonImmutable::now(config('reminders.timezone')))->startOfDay();

        return (int) $from->diffInDays($this->nextOccurrence($from), false);
    }

    /** Anios que cumple en la proxima ocurrencia (null si no sabemos el anio original). */
    public function ageAtNextOccurrence(?CarbonImmutable $from = null): ?int
    {
        if (! $this->year || ! $this->type->countsYears()) {
            return null;
        }

        return $this->nextOccurrence($from)->year - $this->year;
    }

    public function destinationChatId(): ?string
    {
        return $this->chat_id ?: config('waha.default_chat_id');
    }

    public function getFormattedDateAttribute(): string
    {
        $date = $this->occurrenceInYear($this->year ?? now()->year);

        return $this->year
            ? $date->translatedFormat('d \d\e F \d\e Y')
            : $date->translatedFormat('d \d\e F');
    }
}
