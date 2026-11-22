<?php

namespace App\Console\Commands;

use App\Services\AccessControlService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sincroniza el catálogo de permisos y los perfiles base desde config/permissions.php';

    public function handle(AccessControlService $access): int
    {
        $access->syncCatalog();

        $this->components->info('Permisos y perfiles base sincronizados.');

        return self::SUCCESS;
    }
}
