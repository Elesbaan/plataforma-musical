<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('city_name');
            $table->string('venue_name')->nullable();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->decimal('agreed_total', 10, 2);
            $table->decimal('deposit_paid', 10, 2)->default(0);
            $table->enum('status', ['reserved', 'completed', 'cancelled'])->default('reserved');
            $table->text('internal_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
