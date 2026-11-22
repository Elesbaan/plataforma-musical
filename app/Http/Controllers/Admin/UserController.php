<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserAdminService $users,
        private readonly RoleService $roles,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $this->users->paginate($filters),
            'roles' => $this->roles->options(),
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', $this->formProps(null));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = $this->users->save($request->validated(), $request->user());

        return redirect()->route('admin.users.index')->with('success', "Se creó la cuenta de {$user->name}.");
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', $this->formProps($user));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->users->save($request->validated(), $request->user(), $user);

        return redirect()->route('admin.users.index')->with('success', "Se actualizó la cuenta de {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->users->delete($user, $request->user());

        return redirect()->route('admin.users.index')->with('success', "Se eliminó la cuenta de {$user->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?User $user): array
    {
        return [
            'user' => $user ? [
                ...$user->only(['id', 'name', 'email', 'role_id', 'is_active']),
                'service_ids' => $user->services()->pluck('services.id'),
                'is_self' => $user->is(request()->user()),
            ] : null,
            'roles' => $this->roles->options(),
            'services' => $this->users->serviceOptions(),
        ];
    }
}
