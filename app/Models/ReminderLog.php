<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    protected $fillable = [
        'event_id', 'days_before', 'occurrence_date', 'status',
        'chat_id', 'message', 'error', 'waha_message_id', 'sent_at',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'sent_at' => 'datetime',
        'days_before' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function markSent(?string $messageId, string $message): void
    {
        $this->update([
            'status' => 'sent',
            'waha_message_id' => $messageId,
            'message' => $message,
            'sent_at' => now(),
            'error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error' => mb_substr($error, 0, 2000)]);
    }
}
