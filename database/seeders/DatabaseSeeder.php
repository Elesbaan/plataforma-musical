<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CoverageZone;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SiteSetting::create([
            'brand_name' => 'Colectivo Musical Toluca',
            'tagline' => 'Experiencias musicales para celebrar extraordinariamente',
            'seo_description' => 'Grupo versátil y servicios de entretenimiento para bodas, XV años y eventos corporativos.',
            'color_primary' => '#6D28D9',
            'color_secondary' => '#0F172A',
            'color_accent' => '#F59E0B',
            'whatsapp_number' => '527221234567',
            'phone_number' => '722 123 4567',
            'contact_email' => 'reservaciones@colectivomusical.mx',
            'address_text' => 'Toluca, Estado de México',
            'online_discount_active' => true,
            'online_discount_type' => 'percentage',
            'online_discount_value' => 10,
            'online_discount_min_amount' => 0,
            'quote_validity_days' => 7,
            'quote_terms_text' => 'Cotización válida por el periodo indicado. Salón, horarios y disponibilidad sujetos a confirmación.',
            'hero_title' => 'La música que convierte tu evento en una celebración inolvidable',
            'hero_subtitle' => 'Producción musical profesional para bodas, XV años y eventos corporativos.',
        ]);

        // Sin factory: Faker es dependencia de desarrollo y no existe en producción (--no-dev).
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@colectivomusical.mx',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        foreach ([
            ['Toluca / Metepec', 'Estado de México', 'local_hq', 'fixed', 0, 1],
            ['CDMX', 'Ciudad de México', 'foranea', 'fixed', 2500, 2],
            ['Valle de Bravo', 'Estado de México', 'foranea', 'fixed', 2000, 3],
            ['Querétaro', 'Querétaro', 'foranea', 'fixed', 3500, 4],
            ['Otra ciudad', '', 'custom_quote', 'fixed', 0, 5],
        ] as [$city, $state, $type, $feeType, $fee, $sort]) {
            CoverageZone::create(['city_name' => $city, 'state_name' => $state, 'zone_type' => $type, 'travel_fee_type' => $feeType, 'travel_fee_value' => $fee, 'notes' => $type === 'custom_quote' ? 'Viáticos por confirmar según ubicación exacta' : null, 'is_active' => true, 'sort_order' => $sort]);
        }

        $categories = [
            'Grupo Musical Versátil' => ['icon' => 'Music', 'description' => 'Show completo para baile y celebración'],
            'Ceremonias y Cuerdas' => ['icon' => 'Heart', 'description' => 'Música elegante para momentos ceremoniales'],
            'Solistas y Duetos' => ['icon' => 'Mic2', 'description' => 'Propuestas íntimas y versátiles'],
            'Mariachi, Banda y Regional' => ['icon' => 'Guitar', 'description' => 'Tradición mexicana y formato regional'],
            'Audio, Iluminación y Show' => ['icon' => 'Sparkles', 'description' => 'Producción visual y complementos'],
        ];

        $createdCategories = [];
        $i = 1;
        foreach ($categories as $name => $meta) {
            $createdCategories[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name), 'description' => $meta['description'], 'icon' => $meta['icon'], 'sort_order' => $i++, 'is_active' => true]);
        }

        $catalog = [
            ['Grupo Musical Versátil', 'Grupo Versátil Premium', 'core_band', 28500, 'por evento (5 hrs)', 1, true, ['Vocalistas y músicos profesionales', 'Repertorio personalizado', 'Sonido profesional', 'Iluminación básica']],
            ['Grupo Musical Versátil', 'Show Versátil Platinum', 'core_band', 42500, 'por evento (6 hrs)', 1, true, ['Producción ampliada', 'Dos vocalistas', 'Repertorio y puentes musicales', 'DJ entre bloques']],
            ['Ceremonias y Cuerdas', 'Trío de Cuerdas', 'ceremony', 8500, 'por ceremonia', 1, false, ['Entrada de ceremonia', 'Música procesional', 'Repertorio personalizado']],
            ['Solistas y Duetos', 'Teclado y Voz', 'soloist', 6500, 'por evento (3 hrs)', 1, true, ['Tecladista y vocalista', 'Repertorio romántico', 'Sistema de audio básico']],
            ['Solistas y Duetos', 'Dúo Acústico', 'soloist', 9200, 'por evento (4 hrs)', 2, false, ['Guitarra y voz', 'Repertorio versátil', 'Ideal para cóctel']],
            ['Mariachi, Banda y Regional', 'Mariachi Tradicional', 'partner_act', 4800, 'por presentación', 2, true, ['12 piezas musicales', 'Serenata o presentación', 'Repertorio tradicional']],
            ['Audio, Iluminación y Show', 'Pista Luminosa LED', 'addon_extra', 5500, 'por pieza', 2, true, ['Pista modular', 'Iluminación integrada', 'Montaje incluido']],
            ['Audio, Iluminación y Show', 'Robot LED de Impacto', 'addon_extra', 7200, 'por presentación', 2, true, ['Show interactivo', 'Robot led profesional', 'Operador incluido']],
        ];

        foreach ($catalog as $index => [$categoryName, $name, $type, $price, $unit, $capacity, $featured, $features]) {
            $service = Service::create(['category_id' => $createdCategories[$categoryName]->id, 'name' => $name, 'slug' => Str::slug($name), 'short_description' => 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'full_description' => 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'service_type' => $type, 'base_price' => $price, 'price_unit' => $unit, 'daily_capacity' => $capacity, 'is_featured' => $featured, 'is_active' => true, 'sort_order' => $index + 1]);
            foreach ($features as $featureIndex => $feature) {
                $service->features()->create(['feature_text' => $feature, 'sort_order' => $featureIndex + 1]);
            }
        }
    }
}
