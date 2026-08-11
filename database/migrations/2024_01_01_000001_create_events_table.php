<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('birthday');   // birthday | anniversary | custom
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year')->nullable(); // null = no sabemos el anio
            $table->string('chat_id')->nullable();  // destino WhatsApp; null = usa el default
            $table->text('template')->nullable();   // plantilla propia con {nombre}, {edad}...
            $table->boolean('use_ai')->default(false);
            $table->time('send_at')->default('08:00:00');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['month', 'day']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
