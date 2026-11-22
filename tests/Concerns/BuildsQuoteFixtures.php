<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\CoverageZone;
use App\Models\Event;
use App\Models\Quote;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * Datos de catálogo, zonas, configuración y eventos para pruebas del cotizador.
 */
trait BuildsQuoteFixtures
{
    protected function discountSettings(array $overrides = []): SiteSetting
    {
        SiteSetting::query()->delete();

        return SiteSetting::create([
            'brand_name' => 'Colectivo de Prueba',
            'contact_email' => 'agencia@ejemplo.mx',
            'whatsapp_number' => '527221234567',
            'online_discount_active' => true,
            'online_discount_type' => 'percentage',
            'online_discount_value' => 10,
            'online_discount_min_amount' => 0,
            'quote_validity_days' => 7,
            'quote_terms_text' => 'Términos de prueba.',
            ...$overrides,
        ]);
    }

    protected function zone(string $type = CoverageZone::TYPE_LOCAL_HQ, string $feeType = 'fixed', float $fee = 0, array $overrides = []): CoverageZone
    {
        return CoverageZone::create([
            'city_name' => $overrides['city_name'] ?? Str::title($type).' '.Str::random(4),
            'zone_type' => $type,
            'travel_fee_type' => $feeType,
            'travel_fee_value' => $fee,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    protected function catalogService(float $price, int $capacity = 1, array $overrides = []): Service
    {
        $category = Category::firstOrCreate(['slug' => 'general'], ['name' => 'General', 'is_active' => true]);
        $name = $overrides['name'] ?? 'Servicio '.Str::random(6);

        return Service::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'service_type' => 'core_band',
            'base_price' => $price,
            'price_unit' => 'por evento',
            'daily_capacity' => $capacity,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    /**
     * Cotización con sus partidas (sin pasar por el cotizador público).
     *
     * @param  list<array{0: Service, 1: int, 2?: int}>  $lines  [servicio, cantidad, horas extra]
     */
    protected function makeQuote(array $lines, array $overrides = []): Quote
    {
        static $sequence = 0;
        $sequence++;

        $subtotal = 0;
        $items = [];
        foreach ($lines as $line) {
            [$service, $quantity] = $line;
            $hours = $line[2] ?? 0;
            $items[] = ['service_id' => $service->id, 'service_name_snapshot' => $service->name, 'line_type' => 'service', 'quantity' => $quantity, 'unit_price' => $service->base_price, 'line_total' => $service->base_price * $quantity];
            $subtotal += $service->base_price * $quantity;
            if ($hours > 0) {
                $items[] = ['service_id' => $service->id, 'service_name_snapshot' => "Hora extra — {$service->name}", 'line_type' => 'extra_hours', 'quantity' => $hours, 'unit_price' => $service->extra_hour_price, 'line_total' => $service->extra_hour_price * $hours];
                $subtotal += $service->extra_hour_price * $hours;
            }
        }

        $quote = Quote::create([
            'coverage_zone_id' => CoverageZone::firstOrCreate(
                ['city_name' => 'Toluca / Metepec'],
                ['zone_type' => CoverageZone::TYPE_LOCAL_HQ, 'travel_fee_type' => 'fixed', 'travel_fee_value' => 0, 'is_active' => true]
            )->id,
            'folio' => sprintf('COT-2026-%04d', 9000 + $sequence),
            'client_name' => 'Ana López',
            'client_email' => 'ana@ejemplo.mx',
            'client_phone' => '7221234567',
            'event_date' => now()->addMonth()->toDateString(),
            'event_time' => '18:00',
            'event_type' => 'Boda',
            'venue_name' => 'Hacienda San Gabriel',
            'subtotal' => $subtotal,
            'travel_fee' => 0,
            'discount_amount' => 0,
            'total' => $subtotal,
            'valid_until' => now()->addWeek()->toDateString(),
            'status' => 'new',
            ...$overrides,
        ]);
        $quote->items()->createMany($items);

        return $quote;
    }

    protected function reserve(Service $service, string $date, int $quantity = 1, string $status = Event::STATUS_RESERVED): Event
    {
        $event = Event::create([
            'title' => 'Evento '.Str::random(4),
            'event_date' => $date,
            'city_name' => 'Toluca',
            'client_name' => 'Cliente',
            'agreed_total' => 10000,
            'status' => $status,
        ]);
        $event->services()->attach($service->id, ['quantity' => $quantity]);

        return $event;
    }
}
