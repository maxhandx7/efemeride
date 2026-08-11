<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before'); // 0 = el mismo dia
            $table->timestamps();

            $table->unique(['event_id', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_rules');
    }
};
