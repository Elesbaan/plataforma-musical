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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('brand_name')->nullable();
            $table->string('tagline')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('logo_light_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('color_primary', 7)->default('#6D28D9');
            $table->string('color_secondary', 7)->default('#0F172A');
            $table->string('color_accent', 7)->default('#F59E0B');
            $table->string('whatsapp_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address_text')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('spotify_url')->nullable();
            $table->boolean('online_discount_active')->default(true);
            $table->enum('online_discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('online_discount_value', 10, 2)->default(10);
            $table->decimal('online_discount_min_amount', 10, 2)->default(0);
            $table->unsignedInteger('quote_validity_days')->default(7);
            $table->text('quote_terms_text')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_video_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
