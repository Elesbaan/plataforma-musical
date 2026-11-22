<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->superAdmin()->create();
    }

    private function keys(Role $role): array
    {
        return $role->permissions()->orderBy('key')->pluck('key')->all();
    }

    public function test_solo_con_permiso_roles_manage(): void
    {
        $coordinator = User::factory()->withRole(Role::SALES_COORDINATOR)->create();

        $this->actingAs($coordinator)->get('/admin/perfiles')->assertForbidden();
        $this->actingAs($coordinator)->post('/admin/perfiles', ['name' => 'X'])->assertForbidden();
        $this->get('/admin/perfiles')->assertForbidden();
    }

    public function test_lista_perfiles_y_matriz_agrupada_por_modulo(): void
    {
        $this->actingAs($this->admin)->get('/admin/perfiles')
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Roles/Index')->has('roles', 3));

        $this->actingAs($this->admin)->get('/admin/perfiles/crear')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Roles/Form')
                ->has('permissionGroups', count(config('permissions.modules')))
                ->where('permissionGroups.0.module', 'settings')
                ->where('permissionGroups.0.label', 'Identidad y marca'));
    }

    public function test_crea_perfil_con_permisos(): void
    {
        $this->actingAs($this->admin)->post('/admin/perfiles', [
            'name' => 'Socio',
            'description' => 'Socio del colectivo',
            'permissions' => ['quotes.view', 'zones.manage'],
        ])->assertRedirect('/admin/perfiles')->assertSessionHas('success');

        $role = Role::firstWhere('name', 'Socio');
        $this->assertSame('socio', $role->slug);
        $this->assertFalse($role->is_system);
        $this->assertSame(['quotes.view', 'zones.manage'], $this->keys($role));
    }

    public function test_edita_matriz_de_un_perfil_base(): void
    {
        $partner = Role::where('slug', Role::PARTNER)->first();

        $this->actingAs($this->admin)->put("/admin/perfiles/{$partner->id}", [
            'name' => 'Proveedor Aliado',
            'permissions' => ['catalog.manage_own'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['catalog.manage_own'], $this->keys($partner));
        $this->assertSame(Role::PARTNER, $partner->fresh()->slug);
    }

    public function test_super_admin_conserva_todos_los_permisos(): void
    {
        $super = Role::where('slug', Role::SUPER_ADMIN)->first();

        $this->actingAs($this->admin)->put("/admin/perfiles/{$super->id}", [
            'name' => 'Dirección',
            'permissions' => [],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Dirección', $super->fresh()->name);
        $this->assertCount(Permission::count(), $this->keys($super));
    }

    public function test_rechaza_permisos_inexistentes_y_nombre_duplicado(): void
    {
        $this->actingAs($this->admin)->post('/admin/perfiles', [
            'name' => 'Coordinador de Ventas',
            'permissions' => ['inventado.todo'],
        ])->assertSessionHasErrors(['name' => 'Ya existe un perfil con ese nombre.', 'permissions.0']);
    }

    public function test_no_elimina_perfiles_base_ni_perfiles_con_usuarios(): void
    {
        $partner = Role::where('slug', Role::PARTNER)->first();
        $this->actingAs($this->admin)->delete("/admin/perfiles/{$partner->id}")->assertSessionHas('error');
        $this->assertModelExists($partner);

        $custom = Role::create(['name' => 'Temporal', 'slug' => 'temporal']);
        User::factory()->create(['role_id' => $custom->id]);
        $this->actingAs($this->admin)->delete("/admin/perfiles/{$custom->id}")->assertSessionHas('error');
        $this->assertModelExists($custom);
    }

    public function test_elimina_perfil_personalizado_sin_usuarios(): void
    {
        $custom = Role::create(['name' => 'Temporal', 'slug' => 'temporal']);

        $this->actingAs($this->admin)->delete("/admin/perfiles/{$custom->id}")->assertRedirect('/admin/perfiles');

        $this->assertModelMissing($custom);
    }

    public function test_cambios_en_la_matriz_aplican_al_usuario(): void
    {
        $role = Role::create(['name' => 'Ventas Jr', 'slug' => 'ventas-jr']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/admin/zonas')->assertForbidden();

        $this->actingAs($this->admin)->put("/admin/perfiles/{$role->id}", ['name' => 'Ventas Jr', 'permissions' => ['zones.manage']]);

        $this->actingAs($user->fresh())->get('/admin/zonas')->assertOk();
    }
}
