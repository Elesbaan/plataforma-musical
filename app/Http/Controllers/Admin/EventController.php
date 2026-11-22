<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Services\AgendaService;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agenda operativa. EventPolicy autoriza cada acción en las rutas y
 * AgendaService aplica el alcance (toda la agenda o solo eventos propios).
 */
class EventController extends Controller
{
    public function __construct(private readonly AgendaService $agenda) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $month = $this->parseMonth($request->string('mes')->toString());

        return Inertia::render('Admin/Events/Index', [
            'month' => $month->format('Y-m'),
            'view' => $request->input('vista') === 'lista' ? 'lista' : 'mes',
            'events' => $this->agenda->month($user, $month),
            'upcoming' => $this->agenda->upcoming($user),
            'saturatedDays' => (object) $this->agenda->saturatedDays($user, $month),
            'abilities' => [
                'manage' => $user->can('create', Event::class),
                'financials' => $this->agenda->viewsAll($user),
            ],
            'today' => today()->toDateString(),
        ]);
    }

    public function show(Request $request, Event $event): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Events/Show', [
            'event' => $this->agenda->present($event, $user),
            'abilities' => ['manage' => $user->can('update', $event)],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => null,
            'initialDate' => $request->date('fecha')?->toDateString(),
            'services' => $this->agenda->serviceOptions(),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = $this->agenda->save($request->validated(), null, $request->boolean('force_overbooking'));

        return redirect()->route('admin.events.show', $event)->with('success', 'Se agendó el evento.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Form', [
            'event' => $this->agenda->forEdit($event),
            'services' => $this->agenda->serviceOptions($event),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->agenda->save($request->validated(), $event, $request->boolean('force_overbooking'));

        return redirect()->route('admin.events.show', $event)->with('success', 'Se actualizó el evento.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->agenda->delete($event);

        return redirect()->route('admin.events.index')->with('success', 'Se eliminó el evento de la agenda.');
    }

    /**
     * Ocupación de una fecha para las alertas en vivo del formulario.
     */
    public function capacity(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'exclude' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'reserved' => $availability->reservedUnits($data['date'], $data['exclude'] ?? null),
        ]);
    }

    private function parseMonth(string $value): Carbon
    {
        return preg_match('/^\d{4}-\d{2}$/', $value) === 1
            ? Carbon::createFromFormat('Y-m-d', "{$value}-01")->startOfDay()
            : today()->startOfMonth();
    }
}
