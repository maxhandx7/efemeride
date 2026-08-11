<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->date('occurrence_date');             // fecha del evento a la que apunta el aviso
            $table->string('status')->default('queued'); // queued | sent | failed
            $table->string('chat_id')->nullable();
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->string('waha_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Seguro anti-spam: un aviso por evento, antelacion y ocurrencia.
            $table->unique(['event_id', 'days_before', 'occurrence_date'], 'reminder_logs_unique_shot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
