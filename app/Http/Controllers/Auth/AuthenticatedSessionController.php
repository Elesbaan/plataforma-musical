<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $byInactivity = $request->input('reason') === 'inactivity';

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            $byInactivity ? 'error' : 'success',
            $byInactivity
                ? 'Tu sesión se cerró por inactividad. Vuelve a ingresar para continuar.'
                : 'Cerraste sesión correctamente.'
        );
    }

    /**
     * Mantiene viva la sesión mientras hay actividad real en el panel.
     */
    public function keepAlive(): HttpResponse
    {
        return response()->noContent();
    }
}
