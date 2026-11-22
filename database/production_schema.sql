-- ============================================================================
-- Script SQL maestro de producción (instalación limpia desde cero)
-- Compatible con MySQL 8.0+ / MariaDB 10.6+
--
-- ARCHIVO GENERADO: no lo edites a mano. Regenera con:
--     php artisan db:export-production-sql
-- La prueba ProductionSchemaSqlTest falla si este archivo no coincide con
-- las migraciones y el DatabaseSeeder actuales.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla: migrations
CREATE TABLE `migrations` (
  `id` int unsigned not null auto_increment primary key,
  `migration` varchar(255) not null,
  `batch` int not null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: roles
CREATE TABLE `roles` (
  `id` bigint unsigned not null auto_increment primary key,
  `name` varchar(100) not null,
  `slug` varchar(100) not null,
  `description` varchar(255) null,
  `is_system` tinyint(1) not null default '0',
  `created_at` timestamp null,
  `updated_at` timestamp null,
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: users
CREATE TABLE `users` (
  `id` bigint unsigned not null auto_increment primary key,
  `name` varchar(255) not null,
  `email` varchar(255) not null,
  `email_verified_at` timestamp null,
  `password` varchar(255) not null,
  `remember_token` varchar(100) null,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `role_id` bigint unsigned null,
  `is_active` tinyint(1) not null default '1',
  UNIQUE KEY `users_email_unique` (`email`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) on delete restrict
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: password_reset_tokens
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) not null,
  `token` varchar(255) not null,
  `created_at` timestamp null,
  primary key (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sessions
CREATE TABLE `sessions` (
  `id` varchar(255) not null,
  `user_id` bigint unsigned null,
  `ip_address` varchar(45) null,
  `user_agent` text null,
  `payload` longtext not null,
  `last_activity` int not null,
  primary key (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cache
CREATE TABLE `cache` (
  `key` varchar(255) not null,
  `value` mediumtext not null,
  `expiration` int not null,
  primary key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cache_locks
CREATE TABLE `cache_locks` (
  `key` varchar(255) not null,
  `owner` varchar(255) not null,
  `expiration` int not null,
  primary key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: jobs
CREATE TABLE `jobs` (
  `id` bigint unsigned not null auto_increment primary key,
  `queue` varchar(255) not null,
  `payload` longtext not null,
  `attempts` tinyint unsigned not null,
  `reserved_at` int unsigned null,
  `available_at` int unsigned not null,
  `created_at` int unsigned not null,
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: job_batches
CREATE TABLE `job_batches` (
  `id` varchar(255) not null,
  `name` varchar(255) not null,
  `total_jobs` int not null,
  `pending_jobs` int not null,
  `failed_jobs` int not null,
  `failed_job_ids` longtext not null,
  `options` mediumtext null,
  `cancelled_at` int null,
  `created_at` int not null,
  `finished_at` int null,
  primary key (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: failed_jobs
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned not null auto_increment primary key,
  `uuid` varchar(255) not null,
  `connection` text not null,
  `queue` text not null,
  `payload` longtext not null,
  `exception` longtext not null,
  `failed_at` timestamp not null default CURRENT_TIMESTAMP,
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: categories
CREATE TABLE `categories` (
  `id` bigint unsigned not null auto_increment primary key,
  `name` varchar(255) not null,
  `slug` varchar(255) not null,
  `description` text null,
  `icon` varchar(255) null,
  `sort_order` int unsigned not null default '0',
  `is_active` tinyint(1) not null default '1',
  `created_at` timestamp null,
  `updated_at` timestamp null,
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: services
CREATE TABLE `services` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `category_id` bigint unsigned not null,
  `name` varchar(255) not null,
  `slug` varchar(255) not null,
  `short_description` text null,
  `full_description` longtext null,
  `service_type` enum('core_band', 'ceremony', 'soloist', 'partner_act', 'addon_extra') not null,
  `base_price` decimal(10, 2) not null,
  `price_unit` varchar(255) not null default 'por evento',
  `daily_capacity` int unsigned not null default '1',
  `is_featured` tinyint(1) not null default '0',
  `is_active` tinyint(1) not null default '1',
  `sort_order` int unsigned not null default '0',
  `extra_hour_price` decimal(10, 2) null,
  CONSTRAINT `services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) on delete cascade,
  UNIQUE KEY `services_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: site_settings
CREATE TABLE `site_settings` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `brand_name` varchar(255) null,
  `tagline` varchar(255) null,
  `seo_description` text null,
  `logo_light_path` varchar(255) null,
  `logo_dark_path` varchar(255) null,
  `favicon_path` varchar(255) null,
  `color_primary` varchar(7) not null default '#6D28D9',
  `color_secondary` varchar(7) not null default '#0F172A',
  `color_accent` varchar(7) not null default '#F59E0B',
  `whatsapp_number` varchar(255) null,
  `phone_number` varchar(255) null,
  `contact_email` varchar(255) null,
  `address_text` text null,
  `facebook_url` varchar(255) null,
  `instagram_url` varchar(255) null,
  `tiktok_url` varchar(255) null,
  `youtube_url` varchar(255) null,
  `spotify_url` varchar(255) null,
  `online_discount_active` tinyint(1) not null default '1',
  `online_discount_type` enum('percentage', 'fixed') not null default 'percentage',
  `online_discount_value` decimal(10, 2) not null default '10',
  `online_discount_min_amount` decimal(10, 2) not null default '0',
  `quote_validity_days` int unsigned not null default '7',
  `quote_terms_text` text null,
  `hero_title` varchar(255) null,
  `hero_subtitle` text null,
  `hero_video_url` varchar(255) null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: coverage_zones
CREATE TABLE `coverage_zones` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `city_name` varchar(255) not null,
  `state_name` varchar(255) null,
  `zone_type` enum('local_hq', 'foranea', 'custom_quote') not null,
  `travel_fee_type` enum('fixed', 'percentage') not null default 'fixed',
  `travel_fee_value` decimal(10, 2) not null default '0',
  `notes` text null,
  `is_active` tinyint(1) not null default '1',
  `sort_order` int unsigned not null default '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: quotes
CREATE TABLE `quotes` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `folio` varchar(255) not null,
  `client_name` varchar(255) not null,
  `client_email` varchar(255) not null,
  `client_phone` varchar(255) not null,
  `event_date` date not null,
  `event_time` time null,
  `event_type` varchar(255) not null,
  `venue_name` varchar(255) null,
  `coverage_zone_id` bigint unsigned null,
  `custom_city_name` varchar(255) null,
  `subtotal` decimal(10, 2) not null,
  `travel_fee` decimal(10, 2) not null default '0',
  `discount_amount` decimal(10, 2) not null default '0',
  `total` decimal(10, 2) not null,
  `valid_until` date not null,
  `status` enum('new', 'contacted', 'confirmed', 'cancelled') not null default 'new',
  `admin_notes` text null,
  `pdf_path` varchar(255) null,
  CONSTRAINT `quotes_coverage_zone_id_foreign` FOREIGN KEY (`coverage_zone_id`) REFERENCES `coverage_zones` (`id`) on delete set null,
  UNIQUE KEY `quotes_folio_unique` (`folio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: service_features
CREATE TABLE `service_features` (
  `id` bigint unsigned not null auto_increment primary key,
  `service_id` bigint unsigned not null,
  `feature_text` varchar(255) not null,
  `sort_order` int unsigned not null default '0',
  `created_at` timestamp null,
  `updated_at` timestamp null,
  CONSTRAINT `service_features_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: service_media
CREATE TABLE `service_media` (
  `id` bigint unsigned not null auto_increment primary key,
  `service_id` bigint unsigned not null,
  `media_type` enum('image', 'video_local', 'video_embed') not null,
  `file_path` varchar(255) null,
  `embed_url` varchar(255) null,
  `title` varchar(255) null,
  `is_cover` tinyint(1) not null default '0',
  `sort_order` int unsigned not null default '0',
  `created_at` timestamp null,
  `updated_at` timestamp null,
  CONSTRAINT `service_media_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: events
CREATE TABLE `events` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `quote_id` bigint unsigned null,
  `title` varchar(255) not null,
  `event_date` date not null,
  `start_time` time null,
  `end_time` time null,
  `city_name` varchar(255) not null,
  `venue_name` varchar(255) null,
  `client_name` varchar(255) not null,
  `client_phone` varchar(255) null,
  `agreed_total` decimal(10, 2) not null,
  `deposit_paid` decimal(10, 2) not null default '0',
  `status` enum('reserved', 'completed', 'cancelled') not null default 'reserved',
  `internal_notes` text null,
  CONSTRAINT `events_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: quote_items
CREATE TABLE `quote_items` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `quote_id` bigint unsigned not null,
  `service_id` bigint unsigned null,
  `service_name_snapshot` varchar(255) not null,
  `quantity` int unsigned not null default '1',
  `unit_price` decimal(10, 2) not null,
  `line_total` decimal(10, 2) not null,
  `line_type` varchar(20) not null default 'service',
  CONSTRAINT `quote_items_quote_id_foreign` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) on delete cascade,
  CONSTRAINT `quote_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: event_services
CREATE TABLE `event_services` (
  `id` bigint unsigned not null auto_increment primary key,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  `event_id` bigint unsigned not null,
  `service_id` bigint unsigned not null,
  `quantity` int unsigned not null default '1',
  CONSTRAINT `event_services_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) on delete cascade,
  CONSTRAINT `event_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: permissions
CREATE TABLE `permissions` (
  `id` bigint unsigned not null auto_increment primary key,
  `key` varchar(100) not null,
  `module` varchar(50) not null,
  `label` varchar(255) not null,
  `created_at` timestamp null,
  `updated_at` timestamp null,
  UNIQUE KEY `permissions_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: role_permission
CREATE TABLE `role_permission` (
  `role_id` bigint unsigned not null,
  `permission_id` bigint unsigned not null,
  primary key (`role_id`, `permission_id`),
  CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) on delete cascade,
  CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: service_user
CREATE TABLE `service_user` (
  `user_id` bigint unsigned not null,
  `service_id` bigint unsigned not null,
  primary key (`user_id`, `service_id`),
  CONSTRAINT `service_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) on delete cascade,
  CONSTRAINT `service_user_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Datos iniciales
-- Credenciales del administrador: admin@colectivomusical.mx / password
-- IMPORTANTE: cambia la contraseña inmediatamente después del primer acceso.
-- ============================================================================

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrador', 'super-admin', 'Acceso total a la plataforma.', 1, NOW(), NOW()),
(2, 'Coordinador de Ventas', 'coordinador-ventas', 'Atiende cotizaciones y administra la agenda de eventos.', 1, NOW(), NOW()),
(3, 'Proveedor Aliado', 'proveedor-aliado', 'Gestiona la galería y descripción de sus propios servicios y consulta sus eventos.', 1, NOW(), NOW());

INSERT INTO `permissions` (`id`, `key`, `module`, `label`, `created_at`, `updated_at`) VALUES
(1, 'settings.manage', 'settings', 'Administrar identidad, marca, colores y descuentos', NOW(), NOW()),
(2, 'zones.manage', 'zones', 'Administrar ciudades y viáticos', NOW(), NOW()),
(3, 'catalog.manage_all', 'catalog', 'Crear y editar todos los servicios y categorías', NOW(), NOW()),
(4, 'catalog.manage_own', 'catalog', 'Editar galería, descripción y capacidad solo de sus servicios vinculados', NOW(), NOW()),
(5, 'quotes.view', 'quotes', 'Ver cotizaciones', NOW(), NOW()),
(6, 'quotes.manage', 'quotes', 'Atender cotizaciones (estatus, notas y confirmación)', NOW(), NOW()),
(7, 'events.view_all', 'events', 'Ver la agenda completa', NOW(), NOW()),
(8, 'events.view_own', 'events', 'Ver solo los eventos donde participan sus servicios', NOW(), NOW()),
(9, 'events.manage', 'events', 'Crear y editar eventos de la agenda', NOW(), NOW()),
(10, 'users.manage', 'users', 'Administrar usuarios', NOW(), NOW()),
(11, 'roles.manage', 'roles', 'Administrar perfiles y su matriz de permisos', NOW(), NOW());

INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(2, 5),
(2, 6),
(2, 7),
(2, 9),
(3, 4),
(3, 8);

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role_id`, `is_active`) VALUES
(1, 'Administrador', 'admin@colectivomusical.mx', NOW(), '$2y$12$BgxhT8xgUFN4AVJ1.9MKSOv2Ze1KE5j0ImhwV2wn.TvsFGXLf7XY2', NULL, NOW(), NOW(), 1, 1);

INSERT INTO `site_settings` (`id`, `created_at`, `updated_at`, `brand_name`, `tagline`, `seo_description`, `logo_light_path`, `logo_dark_path`, `favicon_path`, `color_primary`, `color_secondary`, `color_accent`, `whatsapp_number`, `phone_number`, `contact_email`, `address_text`, `facebook_url`, `instagram_url`, `tiktok_url`, `youtube_url`, `spotify_url`, `online_discount_active`, `online_discount_type`, `online_discount_value`, `online_discount_min_amount`, `quote_validity_days`, `quote_terms_text`, `hero_title`, `hero_subtitle`, `hero_video_url`) VALUES
(1, NOW(), NOW(), 'Colectivo Musical Toluca', 'Experiencias musicales para celebrar extraordinariamente', 'Grupo versátil y servicios de entretenimiento para bodas, XV años y eventos corporativos.', NULL, NULL, NULL, '#6D28D9', '#0F172A', '#F59E0B', '527221234567', '722 123 4567', 'reservaciones@colectivomusical.mx', 'Toluca, Estado de México', NULL, NULL, NULL, NULL, NULL, 1, 'percentage', 10, 0, 7, 'Cotización válida por el periodo indicado. Salón, horarios y disponibilidad sujetos a confirmación.', 'La música que convierte tu evento en una celebración inolvidable', 'Producción musical profesional para bodas, XV años y eventos corporativos.', NULL);

INSERT INTO `coverage_zones` (`id`, `created_at`, `updated_at`, `city_name`, `state_name`, `zone_type`, `travel_fee_type`, `travel_fee_value`, `notes`, `is_active`, `sort_order`) VALUES
(1, NOW(), NOW(), 'Toluca / Metepec', 'Estado de México', 'local_hq', 'fixed', 0, NULL, 1, 1),
(2, NOW(), NOW(), 'CDMX', 'Ciudad de México', 'foranea', 'fixed', 2500, NULL, 1, 2),
(3, NOW(), NOW(), 'Valle de Bravo', 'Estado de México', 'foranea', 'fixed', 2000, NULL, 1, 3),
(4, NOW(), NOW(), 'Querétaro', 'Querétaro', 'foranea', 'fixed', 3500, NULL, 1, 4),
(5, NOW(), NOW(), 'Otra ciudad', '', 'custom_quote', 'fixed', 0, 'Viáticos por confirmar según ubicación exacta', 1, 5);

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Grupo Musical Versátil', 'grupo-musical-versatil', 'Show completo para baile y celebración', 'Music', 1, 1, NOW(), NOW()),
(2, 'Ceremonias y Cuerdas', 'ceremonias-y-cuerdas', 'Música elegante para momentos ceremoniales', 'Heart', 2, 1, NOW(), NOW()),
(3, 'Solistas y Duetos', 'solistas-y-duetos', 'Propuestas íntimas y versátiles', 'Mic2', 3, 1, NOW(), NOW()),
(4, 'Mariachi, Banda y Regional', 'mariachi-banda-y-regional', 'Tradición mexicana y formato regional', 'Guitar', 4, 1, NOW(), NOW()),
(5, 'Audio, Iluminación y Show', 'audio-iluminacion-y-show', 'Producción visual y complementos', 'Sparkles', 5, 1, NOW(), NOW());

INSERT INTO `services` (`id`, `created_at`, `updated_at`, `category_id`, `name`, `slug`, `short_description`, `full_description`, `service_type`, `base_price`, `price_unit`, `daily_capacity`, `is_featured`, `is_active`, `sort_order`, `extra_hour_price`) VALUES
(1, NOW(), NOW(), 1, 'Grupo Versátil Premium', 'grupo-versatil-premium', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'core_band', 28500, 'por evento (5 hrs)', 1, 1, 1, 1, 4500),
(2, NOW(), NOW(), 1, 'Show Versátil Platinum', 'show-versatil-platinum', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'core_band', 42500, 'por evento (6 hrs)', 1, 1, 1, 2, 6500),
(3, NOW(), NOW(), 2, 'Trío de Cuerdas', 'trio-de-cuerdas', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'ceremony', 8500, 'por ceremonia', 1, 0, 1, 3, NULL),
(4, NOW(), NOW(), 3, 'Teclado y Voz', 'teclado-y-voz', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'soloist', 6500, 'por evento (3 hrs)', 1, 1, 1, 4, 1800),
(5, NOW(), NOW(), 3, 'Dúo Acústico', 'duo-acustico', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'soloist', 9200, 'por evento (4 hrs)', 2, 0, 1, 5, 2200),
(6, NOW(), NOW(), 4, 'Mariachi Tradicional', 'mariachi-tradicional', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'partner_act', 4800, 'por presentación', 2, 1, 1, 6, NULL),
(7, NOW(), NOW(), 5, 'Pista Luminosa LED', 'pista-luminosa-led', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'addon_extra', 5500, 'por pieza', 2, 1, 1, 7, NULL),
(8, NOW(), NOW(), 5, 'Robot LED de Impacto', 'robot-led-de-impacto', 'Servicio profesional diseñado para elevar la experiencia de tu evento.', 'Producción cuidadosamente coordinada, repertorio adaptable y atención profesional antes y durante tu celebración.', 'addon_extra', 7200, 'por presentación', 2, 1, 1, 8, NULL);

INSERT INTO `service_features` (`id`, `service_id`, `feature_text`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vocalistas y músicos profesionales', 1, NOW(), NOW()),
(2, 1, 'Repertorio personalizado', 2, NOW(), NOW()),
(3, 1, 'Sonido profesional', 3, NOW(), NOW()),
(4, 1, 'Iluminación básica', 4, NOW(), NOW()),
(5, 2, 'Producción ampliada', 1, NOW(), NOW()),
(6, 2, 'Dos vocalistas', 2, NOW(), NOW()),
(7, 2, 'Repertorio y puentes musicales', 3, NOW(), NOW()),
(8, 2, 'DJ entre bloques', 4, NOW(), NOW()),
(9, 3, 'Entrada de ceremonia', 1, NOW(), NOW()),
(10, 3, 'Música procesional', 2, NOW(), NOW()),
(11, 3, 'Repertorio personalizado', 3, NOW(), NOW()),
(12, 4, 'Tecladista y vocalista', 1, NOW(), NOW()),
(13, 4, 'Repertorio romántico', 2, NOW(), NOW()),
(14, 4, 'Sistema de audio básico', 3, NOW(), NOW()),
(15, 5, 'Guitarra y voz', 1, NOW(), NOW()),
(16, 5, 'Repertorio versátil', 2, NOW(), NOW()),
(17, 5, 'Ideal para cóctel', 3, NOW(), NOW()),
(18, 6, '12 piezas musicales', 1, NOW(), NOW()),
(19, 6, 'Serenata o presentación', 2, NOW(), NOW()),
(20, 6, 'Repertorio tradicional', 3, NOW(), NOW()),
(21, 7, 'Pista modular', 1, NOW(), NOW()),
(22, 7, 'Iluminación integrada', 2, NOW(), NOW()),
(23, 7, 'Montaje incluido', 3, NOW(), NOW()),
(24, 8, 'Show interactivo', 1, NOW(), NOW()),
(25, 8, 'Robot led profesional', 2, NOW(), NOW()),
(26, 8, 'Operador incluido', 3, NOW(), NOW());

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_11_22_023557_create_categories_table', 1),
(5, '2026_11_22_023557_create_services_table', 1),
(6, '2026_11_22_023557_create_site_settings_table', 1),
(7, '2026_11_22_023558_create_coverage_zones_table', 1),
(8, '2026_11_22_023558_create_quotes_table', 1),
(9, '2026_11_22_023558_create_service_features_table', 1),
(10, '2026_11_22_023558_create_service_media_table', 1),
(11, '2026_11_22_023559_create_events_table', 1),
(12, '2026_11_22_023559_create_quote_items_table', 1),
(13, '2026_11_22_023600_create_event_services_table', 1),
(14, '2026_11_23_000000_create_rbac_tables', 1),
(15, '2026_11_24_000000_add_extra_hour_price_to_services_table', 1),
(16, '2026_11_25_000000_add_line_type_to_quote_items_table', 1);

SET FOREIGN_KEY_CHECKS = 1;
