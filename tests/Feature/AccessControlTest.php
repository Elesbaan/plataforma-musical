<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function permissionsOf(string $slug): array
    {
        return Role::where('slug', $slug)->first()->permissions()->orderBy('key')->pluck('key')->all();
    }

    public function test_catalogo_de_permisos_y_perfiles_base(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(config('permissions.permissions')), Permission::pluck('key')->all());

        $this->assertCount(Permission::count(), $this->permissionsOf(Role::SUPER_ADMIN));
        $this->assertSame(['events.manage', 'events.view_all', 'quotes.manage', 'quotes.view'], $this->permissionsOf(Role::SALES_COORDINATOR));
        $this->assertSame(['catalog.manage_own', 'events.view_own'], $this->permissionsOf(Role::PARTNER));
        $this->assertSame(3, Role::where('is_system', true)->count());
    }

    public function test_sincronizar_es_idempotente_y_respeta_permisos_editados(): void
    {
        $partner = Role::where('slug', Role::PARTNER)->first();
        $partner->permissions()->sync(Permission::where('key', 'events.view_own')->pluck('id'));
        Role::where('slug', Role::SUPER_ADMIN)->first()->permissions()->detach();

        $this->artisan('permissions:sync')->assertSuccessful();
        $this->artisan('permissions:sync')->assertSuccessful();

        $this->assertSame(['events.view_own'], $this->permissionsOf(Role::PARTNER));
        $this->assertCount(Permission::count(), $this->permissionsOf(Role::SUPER_ADMIN));
        $this->assertSame(count(config('permissions.permissions')), Permission::count());
    }

    public function test_migracion_conserva_el_perfil_de_usuarios_existentes(): void
    {
        $migration = require database_path('migrations/2026_11_23_000000_create_rbac_tables.php');
        $migration->down();

        $adminId = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'a@x.mx', 'password' => 'x', 'role' => 'super_admin']);
        $managerId = DB::table('users')->insertGetId(['name' => 'Gerente', 'email' => 'g@x.mx', 'password' => 'x', 'role' => 'manager']);

        $migration->up();

        $this->assertSame(Role::SUPER_ADMIN, User::find($adminId)->role->slug);
        $this->assertSame(Role::SALES_COORDINATOR, User::find($managerId)->role->slug);
        $this->assertTrue(User::find($adminId)->is_active);
    }

    public function test_gates_por_permiso(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $coordinator = User::factory()->withRole(Role::SALES_COORDINATOR)->create();
        $noRole = User::factory()->create();

        foreach (array_keys(config('permissions.permissions')) as $key) {
            $this->assertTrue(Gate::forUser($admin)->allows($key), $key);
            $this->assertFalse(Gate::forUser($noRole)->allows($key), $key);
        }

        $this->assertTrue(Gate::forUser($coordinator)->allows('quotes.manage'));
        $this->assertFalse(Gate::forUser($coordinator)->allows('settings.manage'));
    }

    public function test_usuario_inactivo_no_tiene_permisos_aunque_sea_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->inactive()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('settings.manage'));
        $this->assertSame([], $admin->permissionKeys());
    }

    public function test_coordinador_no_accede_a_modulos_sin_permiso(): void
    {
        $this->actingAs(User::factory()->withRole(Role::SALES_COORDINATOR)->create());

        $this->get('/admin')->assertOk();
        $this->get('/admin/identidad')->assertForbidden();
        $this->get('/admin/zonas')->assertForbidden();
        $this->get('/admin/categorias')->assertForbidden();
        $this->get('/admin/servicios')->assertForbidden();
        $this->post('/admin/zonas', [])->assertForbidden();
    }

    public function test_permisos_se_comparten_con_el_frontend(): void
    {
        $this->actingAs(User::factory()->withRole(Role::SALES_COORDINATOR)->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role.slug', Role::SALES_COORDINATOR)
                ->where('auth.permissions', fn ($keys) => collect($keys)->sort()->values()->all() === ['events.manage', 'events.view_all', 'quotes.manage', 'quotes.view']));

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page->has('auth.permissions', count(config('permissions.permissions'))));
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->superAdmin()->inactive()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);

        $this->assertGuest();
    }

    public function test_usuario_desactivado_con_sesion_abierta_es_expulsado(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->get('/admin')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())
            ->get('/admin/identidad')
            ->assertRedirect('/admin/login')
            ->assertSessionHas('error', 'Tu cuenta está desactivada. Contacta al administrador.');
        $this->assertGuest();
    }
}
