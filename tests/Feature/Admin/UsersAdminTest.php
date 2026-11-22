<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->superAdmin()->create();
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->value('id');
    }

    private function service(string $name): Service
    {
        $category = Category::firstOrCreate(['slug' => 'regional'], ['name' => 'Regional', 'is_active' => true]);

        return Service::create([
            'category_id' => $category->id, 'name' => $name, 'slug' => str($name)->slug(),
            'service_type' => 'partner_act', 'base_price' => 4800, 'price_unit' => 'por presentación',
            'daily_capacity' => 2, 'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Juan Pérez',
            'email' => 'Juan@Mariachi.mx',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'role_id' => $this->roleId(Role::SALES_COORDINATOR),
            'is_active' => true,
            'service_ids' => [],
            ...$overrides,
        ];
    }

    public function test_solo_con_permiso_users_manage(): void
    {
        $coordinator = User::factory()->withRole(Role::SALES_COORDINATOR)->create();

        $this->actingAs($coordinator)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($coordinator)->post('/admin/usuarios', $this->payload())->assertForbidden();
    }

    public function test_lista_y_formulario_con_perfiles_y_servicios(): void
    {
        $this->service('Mariachi Tradicional');

        $this->actingAs($this->admin)->get('/admin/usuarios')
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Index')->has('users.data', 1)->has('roles', 3));

        $this->actingAs($this->admin)->get('/admin/usuarios/crear')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Form')
                ->has('services', 1)
                ->where('roles', fn ($roles) => collect($roles)->firstWhere('slug', Role::PARTNER)['has_own_scope'] === true
                    && collect($roles)->firstWhere('slug', Role::SALES_COORDINATOR)['has_own_scope'] === false));
    }

    public function test_crea_usuario_con_perfil(): void
    {
        $this->actingAs($this->admin)->post('/admin/usuarios', $this->payload())
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHas('success');

        $user = User::firstWhere('email', 'juan@mariachi.mx');
        $this->assertSame(Role::SALES_COORDINATOR, $user->role->slug);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secreto123', $user->password));
    }

    public function test_proveedor_aliado_recibe_servicios_vinculados(): void
    {
        $mariachi = $this->service('Mariachi Tradicional');
        $banda = $this->service('Banda Sinaloense');

        $this->actingAs($this->admin)->post('/admin/usuarios', $this->payload([
            'role_id' => $this->roleId(Role::PARTNER),
            'service_ids' => [$mariachi->id],
        ]))->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'juan@mariachi.mx');
        $this->assertSame([$mariachi->id], $user->services()->pluck('services.id')->all());
        $this->assertTrue($user->ownsService($mariachi->id));
        $this->assertFalse($user->ownsService($banda->id));
    }

    public function test_perfil_sin_alcance_propio_no_conserva_servicios(): void
    {
        $mariachi = $this->service('Mariachi Tradicional');
        $partner = User::factory()->withRole(Role::PARTNER)->create();
        $partner->services()->attach($mariachi);

        $this->actingAs($this->admin)->put("/admin/usuarios/{$partner->id}", $this->payload([
            'email' => $partner->email,
            'password' => '',
            'password_confirmation' => '',
            'role_id' => $this->roleId(Role::SALES_COORDINATOR),
            'service_ids' => [$mariachi->id],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(0, $partner->services()->count());
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $user = User::factory()->withRole(Role::SALES_COORDINATOR)->create();
        $hash = $user->password;

        $this->actingAs($this->admin)->put("/admin/usuarios/{$user->id}", $this->payload([
            'name' => 'Nombre nuevo', 'email' => $user->email, 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Nombre nuevo', $user->fresh()->name);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_validaciones_en_espanol(): void
    {
        $this->actingAs($this->admin)->post('/admin/usuarios', $this->payload([
            'email' => $this->admin->email,
            'password' => 'corta',
            'password_confirmation' => 'distinta',
            'role_id' => 999,
        ]))->assertSessionHasErrors([
            'email' => 'Ya existe un usuario con ese correo.',
            'password',
            'role_id',
        ]);
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->withRole(Role::SALES_COORDINATOR)->create();

        $this->actingAs($this->admin)->put("/admin/usuarios/{$user->id}", $this->payload([
            'email' => $user->email, 'password' => '', 'password_confirmation' => '', 'is_active' => false,
        ]))->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
        $this->assertGuest();
    }

    public function test_no_puede_desactivarse_ni_cambiar_su_propio_perfil(): void
    {
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($this->admin)->from("/admin/usuarios/{$this->admin->id}/editar")
            ->put("/admin/usuarios/{$this->admin->id}", $this->payload([
                'email' => $this->admin->email, 'password' => '', 'password_confirmation' => '',
                'role_id' => $this->roleId(Role::SUPER_ADMIN), 'is_active' => false,
            ]))
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->actingAs($this->admin)
            ->put("/admin/usuarios/{$this->admin->id}", $this->payload([
                'email' => $this->admin->email, 'password' => '', 'password_confirmation' => '',
                'role_id' => $this->roleId(Role::SALES_COORDINATOR),
            ]))
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertTrue($this->admin->fresh()->isSuperAdmin());
        $this->assertNotNull($other);
    }

    public function test_siempre_queda_un_super_admin_activo(): void
    {
        $actor = User::factory()->withRole(Role::SALES_COORDINATOR)->create();
        $actor->role->permissions()->attach(\App\Models\Permission::where('key', 'users.manage')->value('id'));

        $this->actingAs($actor->fresh())
            ->put("/admin/usuarios/{$this->admin->id}", $this->payload([
                'email' => $this->admin->email, 'password' => '', 'password_confirmation' => '',
                'role_id' => $this->roleId(Role::SUPER_ADMIN), 'is_active' => false,
            ]))
            ->assertSessionHas('error', 'Debe existir al menos un Super Administrador activo.');

        $this->actingAs($actor->fresh())->delete("/admin/usuarios/{$this->admin->id}")->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_elimina_usuario_pero_no_a_si_mismo(): void
    {
        $user = User::factory()->withRole(Role::PARTNER)->create();

        $this->actingAs($this->admin)->delete("/admin/usuarios/{$user->id}")->assertRedirect('/admin/usuarios');
        $this->assertModelMissing($user);

        $this->actingAs($this->admin)->delete("/admin/usuarios/{$this->admin->id}")
            ->assertSessionHas('error', 'No puedes eliminar tu propia cuenta.');
        $this->assertModelExists($this->admin);
    }
}
