<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderRule extends Model
{
    protected $fillable = ['event_id', 'days_before'];

    protected $casts = ['days_before' => 'integer'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function label(): string
    {
        return match (true) {
            $this->days_before === 0 => 'El mismo dia',
            $this->days_before === 1 => 'Un dia antes',
            $this->days_before === 7 => 'Una semana antes',
            default => "{$this->days_before} dias antes",
        };
    }
}
