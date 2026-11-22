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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('folio')->unique();
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone');
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('event_type');
            $table->string('venue_name')->nullable();
            $table->foreignId('coverage_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_city_name')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('travel_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->date('valid_until');
            $table->enum('status', ['new', 'contacted', 'confirmed', 'cancelled'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->string('pdf_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
