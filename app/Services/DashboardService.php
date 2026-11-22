<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Quote;
use App\Models\User;

/**
 * Métricas del dashboard según los permisos del usuario autenticado.
 * Cada bloque solo se calcula (y se envía) si el usuario puede verlo.
 */
class DashboardService
{
    public function __construct(private readonly AgendaService $agenda) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $data = ['stats' => [], 'latestQuotes' => null, 'weekEvents' => null, 'myServices' => null];

        if ($user->can('quotes.view')) {
            $monthQuotes = Quote::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

            $data['stats']['new_quotes'] = Quote::where('status', Quote::STATUS_NEW)->count();
            $data['stats']['month_quoted'] = number_format((float) (clone $monthQuotes)->sum('total'), 2, '.', '');
            $data['stats']['month_quotes_count'] = (clone $monthQuotes)->count();
            $data['latestQuotes'] = Quote::query()
                ->latest('id')
                ->limit(6)
                ->get(['id', 'folio', 'client_name', 'event_type', 'event_date', 'total', 'status'])
                ->map(fn (Quote $quote) => [
                    ...$quote->only(['id', 'folio', 'client_name', 'event_type', 'total', 'status']),
                    'event_date' => $quote->event_date->toDateString(),
                ]);
        }

        if ($this->agenda->viewsAll($user)) {
            $reserved = Event::query()->where('status', Event::STATUS_RESERVED);

            $data['stats']['upcoming_events'] = (clone $reserved)->whereDate('event_date', '>=', today())->count();
            $data['stats']['pending_balance'] = number_format(
                (float) (clone $reserved)->selectRaw('COALESCE(SUM(agreed_total - deposit_paid), 0) as balance')->value('balance'),
                2, '.', ''
            );
            $data['weekEvents'] = $this->agenda->upcoming($user, 10, 7);
        } elseif ($user->can('events.view_own')) {
            $mine = $this->agenda->upcoming($user, 10);
            $data['stats']['my_upcoming_events'] = count($this->agenda->upcoming($user, 500));
            $data['weekEvents'] = $mine;
        }

        if ($user->can('catalog.manage_own') && ! $user->can('catalog.manage_all')) {
            $data['myServices'] = $user->services()->orderBy('name')->get(['services.id', 'services.name', 'services.is_active'])
                ->map(fn ($service) => $service->only(['id', 'name', 'is_active']));
        }

        return $data;
    }
}
