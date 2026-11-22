<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_invitado_es_redirigido_al_login_desde_el_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_muestra_la_pagina_de_acceso(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_administrador_inicia_sesion_y_ve_el_dashboard(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.user.email', $user->email)
                ->where('auth.user.role.slug', 'super-admin')
                ->missing('auth.user.password')
            );
    }

    public function test_credenciales_incorrectas_muestran_error_en_espanol(): void
    {
        $user = User::factory()->create();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_login_se_bloquea_tras_demasiados_intentos(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $_) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_autenticado_no_ve_el_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/login')
            ->assertRedirect('/admin');
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout')
            ->assertRedirect('/admin/login')
            ->assertSessionHas('success');

        $this->assertGuest();
    }

    public function test_login_tambien_responde_en_ruta_corta(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));

        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_no_crea_cookie_de_recordar_sesion(): void
    {
        $user = User::factory()->create(['remember_token' => null]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->assertRedirect('/admin')
            ->assertCookieMissing(auth()->guard('web')->getRecallerName());

        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_sesion_expira_al_cerrar_navegador_y_tras_30_minutos(): void
    {
        $this->assertTrue(config('session.expire_on_close'));
        $this->assertSame(30, config('session.lifetime'));
    }

    public function test_panel_comparte_duracion_de_sesion_para_temporizador(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page->where('auth.sessionLifetime', 30));
    }

    public function test_keep_alive_requiere_sesion_y_responde_sin_contenido(): void
    {
        $this->postJson('/admin/session/keep-alive')->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->post('/admin/session/keep-alive')
            ->assertNoContent();
    }

    public function test_cierre_por_inactividad_muestra_aviso(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout', ['reason' => 'inactivity'])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('error', 'Tu sesión se cerró por inactividad. Vuelve a ingresar para continuar.');

        $this->assertGuest();
    }
}
