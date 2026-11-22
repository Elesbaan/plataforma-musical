<?php

use App\Exceptions\ActionNotAllowedException;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias(['active' => EnsureUserIsActive::class]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Reglas de negocio que bloquean una acción: regresar con aviso flash.
        $exceptions->render(function (ActionNotAllowedException $e, Request $request) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });

        // Sesión o token CSRF expirado (419): volver al login con un mensaje claro
        // en lugar de mostrar la pantalla de error genérica.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => 'Tu sesión expiró. Vuelve a ingresar.'], 419);
            }

            return redirect()->route('login')->with('error', 'Tu sesión expiró. Vuelve a ingresar para continuar.');
        });
    })->create();
