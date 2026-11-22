<?php

namespace App\Services;

use App\Models\CoverageZone;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Único responsable de calcular cotizaciones (AGENTS.md §2):
 *
 *   Subtotal  = Σ (precio_base × cantidad) + Σ (tarifa_hora_extra × horas)
 *   Viáticos  = según la zona: $0 sede local / fijo o % del subtotal foránea / $0 "por confirmar"
 *   Descuento = si está activo y Subtotal ≥ monto mínimo: % del subtotal o monto fijo (tope: subtotal)
 *   Total     = Subtotal − Descuento + Viáticos
 *
 * Siempre usa precios de la base de datos; nunca montos enviados por el navegador.
 * Los importes se calculan en centavos enteros para evitar errores de redondeo.
 */
class QuoteCalculatorService
{
    public const LINE_SERVICE = 'service';

    public const LINE_EXTRA_HOURS = 'extra_hours';

    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @param  list<array{service_id: int|string, quantity?: int|string, extra_hours?: int|string}>  $items
     * @param  string|null  $date  Si se indica, se valida la disponibilidad real de cada servicio.
     * @return array{
     *     lines: list<array{service_id: int, name: string, kind: string, quantity: int, unit_price: float, line_total: float}>,
     *     subtotal: float, travel_fee: float, travel_pending: bool, travel_label: ?string,
     *     discount_amount: float, discount_applies: bool, discount_label: ?string, discount_missing: float,
     *     total: float
     * }
     *
     * @throws ValidationException Si un servicio no existe, está inactivo o no tiene cupo.
     */
    public function calculate(array $items, ?CoverageZone $zone, ?string $date = null): array
    {
        $services = Service::query()
            ->where('is_active', true)
            ->whereIn('id', collect($items)->pluck('service_id')->map(fn ($id) => (int) $id)->all())
            ->get()
            ->keyBy('id');

        $remaining = $date ? $this->availability->forDate($date) : [];
        $errors = [];
        $lines = [];
        $seen = [];

        foreach (array_values($items) as $index => $item) {
            $service = $services->get((int) $item['service_id']);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $extraHours = max(0, (int) ($item['extra_hours'] ?? 0));

            if (! $service) {
                $errors["items.{$index}.service_id"] = 'Este servicio ya no está disponible. Quítalo de tu cotización.';

                continue;
            }

            // Un servicio repetido evadiría la validación de capacidad por partida.
            if (isset($seen[$service->id])) {
                $errors["items.{$index}.service_id"] = "{$service->name} aparece más de una vez. Ajusta la cantidad en una sola partida.";

                continue;
            }
            $seen[$service->id] = true;

            if ($date) {
                $free = $remaining[$service->id]['remaining'] ?? 0;
                if ($free === 0) {
                    $errors["items.{$index}.quantity"] = "{$service->name} ya no tiene disponibilidad para el ".$this->formatDate($date).'.';

                    continue;
                }
                if ($quantity > $free) {
                    $errors["items.{$index}.quantity"] = "Solo quedan {$free} disponibles de {$service->name} para esa fecha.";

                    continue;
                }
            }

            if ($extraHours > 0 && $service->extra_hour_price === null) {
                $errors["items.{$index}.extra_hours"] = "{$service->name} no ofrece horas extra.";

                continue;
            }

            $lines[] = $this->line($service, self::LINE_SERVICE, $service->name, $quantity, $this->cents($service->base_price));

            if ($extraHours > 0) {
                $lines[] = $this->line($service, self::LINE_EXTRA_HOURS, "Hora extra — {$service->name}", $extraHours, $this->cents($service->extra_hour_price));
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $subtotal = array_sum(array_column($lines, 'line_total_cents'));
        [$travel, $travelPending, $travelLabel] = $this->travel($zone, $subtotal);
        [$discount, $discountApplies, $discountLabel, $discountMissing] = $this->discount($subtotal);

        return [
            'lines' => array_map(fn ($line) => [
                'service_id' => $line['service_id'],
                'name' => $line['name'],
                'kind' => $line['kind'],
                'quantity' => $line['quantity'],
                'unit_price' => $this->money($line['unit_price_cents']),
                'line_total' => $this->money($line['line_total_cents']),
            ], $lines),
            'subtotal' => $this->money($subtotal),
            'travel_fee' => $this->money($travel),
            'travel_pending' => $travelPending,
            'travel_label' => $travelLabel,
            'discount_amount' => $this->money($discount),
            'discount_applies' => $discountApplies,
            'discount_label' => $discountLabel,
            'discount_missing' => $this->money($discountMissing),
            'total' => $this->money($subtotal - $discount + $travel),
        ];
    }

    /**
     * @return array{0: int, 1: bool, 2: ?string} [centavos, por confirmar, leyenda]
     */
    private function travel(?CoverageZone $zone, int $subtotal): array
    {
        if (! $zone) {
            return [0, false, null];
        }

        return match ($zone->zone_type) {
            CoverageZone::TYPE_LOCAL_HQ => [0, false, 'Sin costo de traslado'],
            CoverageZone::TYPE_CUSTOM_QUOTE => [0, true, $zone->notes ?: config('quotes.travel_pending_label')],
            default => $zone->travel_fee_type === 'percentage'
                ? [$this->percentOf($subtotal, $zone->travel_fee_value), false, $this->percentLabel($zone->travel_fee_value).' del subtotal']
                : [$this->cents($zone->travel_fee_value), false, null],
        };
    }

    /**
     * @return array{0: int, 1: bool, 2: ?string, 3: int} [centavos, aplica, leyenda, faltante para el mínimo]
     */
    private function discount(int $subtotal): array
    {
        $settings = SiteSetting::current();

        if (! $settings->online_discount_active || $subtotal <= 0) {
            return [0, false, null, 0];
        }

        $minimum = $this->cents($settings->online_discount_min_amount ?? 0);
        $isPercentage = $settings->online_discount_type === 'percentage';
        $label = $isPercentage
            ? 'Descuento exclusivo web ('.$this->percentLabel($settings->online_discount_value).')'
            : 'Descuento exclusivo web';

        if ($subtotal < $minimum) {
            return [0, false, $label, $minimum - $subtotal];
        }

        $amount = $isPercentage
            ? $this->percentOf($subtotal, $settings->online_discount_value)
            : min($this->cents($settings->online_discount_value), $subtotal);

        return [$amount, true, $label, 0];
    }

    /**
     * @return array<string, mixed>
     */
    private function line(Service $service, string $kind, string $name, int $quantity, int $unitCents): array
    {
        return [
            'service_id' => $service->id,
            'name' => $name,
            'kind' => $kind,
            'quantity' => $quantity,
            'unit_price_cents' => $unitCents,
            'line_total_cents' => $unitCents * $quantity,
        ];
    }

    private function cents(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function percentOf(int $cents, string|int|float $percent): int
    {
        return (int) round($cents * ((float) $percent) / 100);
    }

    private function money(int $cents): float
    {
        return round($cents / 100, 2);
    }

    private function percentLabel(string|int|float $percent): string
    {
        return rtrim(rtrim(number_format((float) $percent, 2, '.', ''), '0'), '.').'%';
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->locale('es')->translatedFormat('j \d\e F \d\e Y');
    }
}
