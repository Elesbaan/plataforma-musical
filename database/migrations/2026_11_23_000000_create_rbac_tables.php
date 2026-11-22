<?php

use App\Models\Role;
use App\Services\AccessControlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sistema de perfiles y permisos (RBAC).
 *
 * Reemplaza la columna `users.role` (enum) por `users.role_id`, conservando el
 * perfil de los usuarios existentes: super_admin -> Super Administrador y
 * manager -> Coordinador de Ventas.
 */
return new class extends Migration
{
    private const LEGACY_ROLE_MAP = [
        'super_admin' => 'super-admin',
        'manager' => 'coordinador-ventas',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('module', 50);
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('service_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'service_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
        });

        // Datos: catálogo base y conversión del perfil anterior (no aplica al
        // generar el SQL maestro en modo "pretend").
        if (! DB::connection()->pretending()) {
            app(AccessControlService::class)->syncCatalog();

            foreach (self::LEGACY_ROLE_MAP as $legacy => $slug) {
                DB::table('users')->where('role', $legacy)->update([
                    'role_id' => Role::where('slug', $slug)->value('id'),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'manager'])->default('manager');
        });

        foreach (self::LEGACY_ROLE_MAP as $legacy => $slug) {
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');
            if ($roleId) {
                DB::table('users')->where('role_id', $roleId)->update(['role' => $legacy]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('service_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
