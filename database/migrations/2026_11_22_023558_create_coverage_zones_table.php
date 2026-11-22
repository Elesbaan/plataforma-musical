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
        Schema::create('coverage_zones', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('city_name');
            $table->string('state_name')->nullable();
            $table->enum('zone_type', ['local_hq', 'foranea', 'custom_quote']);
            $table->enum('travel_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('travel_fee_value', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coverage_zones');
    }
};
