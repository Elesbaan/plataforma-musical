<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de partida de una cotización: el servicio en sí ('service') o sus horas
 * extra ('extra_hours'). Permite convertir cotizaciones en eventos sin depender
 * del texto de la partida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->string('line_type', 20)->default('service');
        });

        // Partidas existentes de horas extra (no aplica al generar el SQL maestro).
        if (! DB::connection()->pretending()) {
            DB::table('quote_items')
                ->where('service_name_snapshot', 'like', 'Hora extra — %')
                ->update(['line_type' => 'extra_hours']);
        }
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn('line_type');
        });
    }
};
