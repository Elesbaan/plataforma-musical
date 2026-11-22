<?php

/*
| Catálogo de permisos y perfiles base del sistema (RBAC).
|
| - `permissions`: única fuente de verdad de las claves de permiso. Se sincronizan
|   con la tabla `permissions` mediante `php artisan permissions:sync` (idempotente),
|   que también se ejecuta en la migración RBAC y en el DatabaseSeeder.
| - `roles`: perfiles protegidos (is_system). El Super Administrador siempre tiene
|   todos los permisos; los demás reciben su lista solo al crearse, después se
|   administran desde el panel.
| - Las claves que terminan en `_own` limitan el alcance a los servicios vinculados
|   al usuario (tabla service_user).
*/

return [

    'modules' => [
        'settings' => 'Identidad y marca',
        'zones' => 'Zonas y viáticos',
        'catalog' => 'Catálogo de servicios',
        'quotes' => 'Cotizaciones',
        'events' => 'Agenda de eventos',
        'users' => 'Usuarios',
        'roles' => 'Perfiles y permisos',
    ],

    'permissions' => [
        'settings.manage' => ['module' => 'settings', 'label' => 'Administrar identidad, marca, colores y descuentos'],
        'zones.manage' => ['module' => 'zones', 'label' => 'Administrar ciudades y viáticos'],
        'catalog.manage_all' => ['module' => 'catalog', 'label' => 'Crear y editar todos los servicios y categorías'],
        'catalog.manage_own' => ['module' => 'catalog', 'label' => 'Editar galería, descripción y capacidad solo de sus servicios vinculados'],
        'quotes.view' => ['module' => 'quotes', 'label' => 'Ver cotizaciones'],
        'quotes.manage' => ['module' => 'quotes', 'label' => 'Atender cotizaciones (estatus, notas y confirmación)'],
        'events.view_all' => ['module' => 'events', 'label' => 'Ver la agenda completa'],
        'events.view_own' => ['module' => 'events', 'label' => 'Ver solo los eventos donde participan sus servicios'],
        'events.manage' => ['module' => 'events', 'label' => 'Crear y editar eventos de la agenda'],
        'users.manage' => ['module' => 'users', 'label' => 'Administrar usuarios'],
        'roles.manage' => ['module' => 'roles', 'label' => 'Administrar perfiles y su matriz de permisos'],
    ],

    'roles' => [
        'super-admin' => [
            'name' => 'Super Administrador',
            'description' => 'Acceso total a la plataforma.',
            'permissions' => '*',
        ],
        'coordinador-ventas' => [
            'name' => 'Coordinador de Ventas',
            'description' => 'Atiende cotizaciones y administra la agenda de eventos.',
            'permissions' => ['quotes.view', 'quotes.manage', 'events.view_all', 'events.manage'],
        ],
        'proveedor-aliado' => [
            'name' => 'Proveedor Aliado',
            'description' => 'Gestiona la galería y descripción de sus propios servicios y consulta sus eventos.',
            'permissions' => ['catalog.manage_own', 'events.view_own'],
        ],
    ],

];
