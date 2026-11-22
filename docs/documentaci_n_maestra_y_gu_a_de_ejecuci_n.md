# CLAUDE_MASTER_SPEC.md — Plataforma Integral de Colectivo Musical y Eventos

> **INSTRUCCIÓN PARA CLAUDE CODE (VS CODE):**
>
> Este documento es la **única fuente de verdad** (Arquitectura, Reglas de Negocio, Posicionamiento de Marca, Diseño UI/UX, Base de Datos, Restricciones de Hosting y Plan de Ejecución Paso a Paso). Léelo íntegramente antes de escribir código y ejecútalo estrictamente por fases, verificando cada módulo antes de avanzar al siguiente.

---

## 1. Posicionamiento Estratégico y Pilares de Diferenciación (Benchmarking del Sector)

La plataforma está diseñada para competir en el segmento **VIP y de Alto Perfil** dentro de la industria de eventos sociales y corporativos (Bodas, XV Años, Graduaciones y Convenciones Empresariales), superando el estándar tradicional del mercado mediante 6 pilares nativos:

1. **Estética Editorial y Aspiracional (Producción de Lujo):**
   - El sitio debe proyectar elegancia, solidez y producción escénica de primer nivel (evitando la apariencia de grupo musical informal o plantillas genéricas lentas).
   - Uso de narrativa persuasiva enfocada en experiencias memorables, energía en pista y excelencia musical.
2. **Transparencia de Precios con Anclaje Estratégico ("Desde $XX,XXX MXN"):**
   - Precios base visibles en cada servicio y paquete para filtrar prospectos no calificados, generar confianza inmediata y acelerar la toma de decisiones.
3. **Arquitectura Modular (Paquetes Base + Amenidades/Extras):**
   - Estructuración clara entre alineaciones musicales principales (Grupo Versátil, Ceremonias, Solistas) y complementos de alto impacto visual (Robots LED, Pistas Iluminadas, Pantallas de Karaoke, Botargas, Pirotecnia fría, Mariachi, Banda, DJ), permitiendo armar eventos a la medida.
4. **Autoservicio Interactivo y Conversión Inmediata (Cotizador + PDF):**
   - A diferencia de los sitios tradicionales donde el cliente debe esperar horas o días por una cotización manual, nuestra plataforma ofrece armado de paquetes en tiempo real, validación instantánea de disponibilidad por fecha, cálculo automático de viáticos por ciudad, descuento exclusivo por cotizar en línea y descarga inmediata de cotización formal en PDF.
5. **Prueba Social y Evidencia Multimedia en Vivo:**
   - Enfoque fuerte en videos cortos en vivo organizados por ambiente/bloque musical, repertorios curados (enlaces a playlists de Spotify/Apple Music), galería fotográfica de producciones reales y sección de clientes/salones destacados.
6. **Optimización SEO Local y por Tipo de Evento:**
   - Estructura semántica preparada para posicionar búsquedas por tipo de evento (*Bodas, XV Años, Eventos Corporativos*) y por plazas geográficas (*Toluca, Metepec, CDMX, Valle de Bravo, Querétaro, Cuernavaca*).

---

## 2. Visión del Proyecto e Identidad Dinámica (White-Label)

### Concepto de Negocio
Plataforma web integral tipo agencia/colectivo musical con sede base en **Toluca, Estado de México**, diseñada para que una agrupación de músicos profesionales comercialice tanto sus propios formatos musicales como servicios complementarios de entretenimiento bajo una misma marca consolidada.

### Identidad 100% Configurable desde el Panel Admin (Sin Nombre Fijo Inicial)
Dado que la identidad gráfica está en evolución y puede adaptarse o escalar, **ningún nombre de marca, logotipo o paleta de colores debe ir hardcodeado en el código**:
- Desde el módulo **Identidad y Apariencia** del Panel Admin, el administrador podrá:
  1. Editar el **Nombre Comercial**, **Eslogan**, **Descripción SEO**, **Teléfono**, **WhatsApp principal**, **Correo de contacto** y **Redes Sociales** (Facebook, Instagram, TikTok, YouTube, Spotify).
  2. Subir **Logo Versión Clara**, **Logo Versión Oscura** y **Favicon dinámico** (inyectado automáticamente en `<link rel="icon">`).
  3. Elegir mediante color pickers los **Colores de Marca** (`Primario`, `Secundario`, `Acento`), los cuales Laravel inyectará como variables CSS globales (`:root`) para transformar toda la identidad visual del sitio instantáneamente.

---

## 3. Arquitectura Tecnológica y Restricciones de Servidor (DonWeb / Ferozo)

### Entorno de Producción Destino
- **Hosting:** Servidor compartido revendedor con **Panel Ferozo en DonWeb** (Linux + Apache).
- **Restricciones Estrictas:**
  - **NO** habrá procesos Node.js, PM2, Docker ni Python corriendo en el servidor de producción.
  - Todo el backend debe ser **PHP 8.2+ nativo con Laravel 11**.
  - Los assets de React/Tailwind se compilan con Vite (`npm run build`) en entorno local y se despliegan listos dentro de `public/build/`.
  - Base de datos **MySQL / MariaDB** estándar.
  - Subida de videos pesados mediante **Chunked Upload (Subida por Bloques)** para evadir los límites `upload_max_filesize` y `post_max_size` del PHP compartido.

### Stack Oficial
- **Backend:** Laravel 11.x (PHP 8.2+)
- **Full-Stack Bridge:** Inertia.js (`inertiajs/inertia-laravel` + `@inertiajs/react`) — navegación tipo SPA ultrarrápida sin complejidad de API stateless separada.
- **Frontend:** React 18+ (Functional Components + Hooks) + Tailwind CSS
- **Iconografía:** `lucide-react`
- **Generación de PDF:** `barryvdh/laravel-dompdf`
- **Correos:** Laravel Mail (configurable vía SMTP en `.env`)

---

## 4. Roadmap por Etapas

### ETAPA 1: MVP Comercial y Operativo (Construcción Inmediata)
1. **Motor White-Label y Configuración Global (`SiteSettings`).**
2. **Widget Flotante de Accesibilidad Universal (WCAG 2.1 AA) con `localStorage`.**
3. **Catálogo Público de Servicios, Paquetes, Tipos de Evento (Bodas, XV, Empresariales) y Amenidades.**
4. **Gestor Multimedia con Subida de Videos por Bloques (Chunked Upload) y Embeds (YouTube/Vimeo).**
5. **Cotizador Interactivo en Línea:**
   - Validación de disponibilidad en tiempo real por fecha (`daily_capacity`).
   - Cálculo de traslado/viáticos por ciudad/zona (`CoverageZones`).
   - Descuento exclusivo configurable por cotizar en línea.
   - Captura de Lead (Nombre, Email, WhatsApp, Fecha, Tipo de Evento, Salón) para desbloquear descarga inmediata de **PDF formal** + envío automático por email + botón de WhatsApp con folio.
6. **Panel Administrativo (CMS + Mini-CRM + Agenda Multi-Evento):**
   - Dashboard de métricas clave.
   - CRUD de Categorías, Servicios (con características incluidas y `daily_capacity`) y Zonas de Cobertura/Sedes.
   - Mini-CRM de Cotizaciones (filtros, cambio de estatus, notas internas, botón directo WhatsApp, conversión a evento confirmado).
   - **Agenda / Calendario Multi-Evento:** Permite múltiples eventos el mismo día respetando la capacidad diaria de cada servicio y mostrando alertas de ocupación.

### ETAPA 2: Coordinación del Colectivo (Futuro - Dejar estructura preparada)
- Directorio de músicos integrantes y proveedores aliados.
- Asignación de músicos/proveedores a eventos confirmados y alertas automáticas por correo/WhatsApp.
- Control de anticipos, saldos y pagos a músicos.

### ETAPA 3: Cobros en Línea y Contratos Digitales (Futuro)
- Pasarela de pagos (Mercado Pago / Stripe) para apartar fechas en línea.
- Generación y firma digital de contratos.

---

## 5. Reglas de Negocio Detalladas (Lógica Crítica)

### A. Control de Capacidad y Disponibilidad por Fecha (`daily_capacity`)
- Cada servicio en `services` tiene un campo entero `daily_capacity` (default `1`) editable desde el panel admin:
  - *Ejemplo:* Grupo Versátil = `1`, Teclado y Voz = `1`, Mariachi Aliado = `2`, Pista Luminosa = `2`.
  - Si en el futuro la plantilla crece o se suman más alineaciones/aliados, el administrador simplemente incrementa este número.
- **Validación en Tiempo Real:**
  - Las cotizaciones enviadas (`quotes`) **NO bloquean** una fecha; solo los eventos confirmados (`events` con `status = 'reserved'`) descuentan disponibilidad.
  - Cuando el cliente selecciona una fecha en el cotizador público, el frontend consulta `/api/availability?date=YYYY-MM-DD`.
  - Si la suma de unidades reservadas de un servicio en esa fecha es `>= daily_capacity`, el servicio se muestra como **"No disponible en esta fecha"** y se impide agregarlo.
  - Cuando el administrador cambia una cotización a `confirmed`, el sistema crea automáticamente el registro en `events`, copia las partidas a `event_services` y descuenta esa capacidad para la fecha.

### B. Zonas de Cobertura, Viáticos y Futuras Sedes (`coverage_zones`)
- Tabla administrable de ciudades/zonas:
  - **Toluca / Metepec (Zona Base):** `zone_type = 'local_hq'`, cargo de viáticos = `$0.00 MXN`.
  - **Ciudades Foráneas (ej. CDMX, Valle de Bravo, Querétaro, Cuernavaca):** `zone_type = 'foranea'`, cargo fijo (ej. `$2,500.00 MXN`) o porcentual configurable desde el panel.
  - **Otra Ciudad:** `zone_type = 'custom_quote'`, permite cotizar con `$0` de viáticos iniciales pero marca la cotización y el PDF con la leyenda *"Viáticos por confirmar según ubicación exacta"*.
  - **Escalabilidad a Sedes:** Si en el futuro abren una sede local con músicos propios en otra ciudad, el admin solo cambia su `zone_type` a `local_hq` con `$0` de cargo.

### C. Cotizador en Línea, Descuento Web y PDF
- **Cálculo:**
  - `Subtotal` = Suma de `(precio_base * cantidad)` de todos los servicios y extras seleccionados.
  - `Viáticos` = Cargo según la `CoverageZone` elegida.
  - `Descuento Web` = Si `online_discount_active == true` y `Subtotal >= online_discount_min_amount`, aplica descuento (`percentage` o `fixed`) sobre el `Subtotal`.
  - `Total` = `Subtotal - Descuento Web + Viáticos`.
- **Validación Backend Obligatoria:** React muestra el cálculo en vivo, pero `QuoteController@store` recalcula todo en el servidor usando los precios reales de la base de datos para evitar manipulaciones.
- **Generación de PDF (`barryvdh/laravel-dompdf`):**
  - Folio secuencial único: `COT-YYYY-NNNN` (ej. `COT-2026-0001`).
  - Diseño editorial elegante usando el logo y colores dinámicos de `site_settings`.
  - Incluye: Datos de la agencia, datos del cliente y evento, tabla desglosada de servicios, subtotal, ahorro obtenido por cotizar en línea, viáticos, total final, fecha de vigencia (`created_at + quote_validity_days`) y términos y condiciones configurables.
  - Se guarda en `storage/app/public/quotes/{folio}.pdf`, se envía adjunto por correo al cliente, se notifica al correo del admin y se descarga automáticamente en el navegador del usuario.

### D. Accesibilidad Universal (WCAG 2.1 AA)
- Componente `<AccessibilityWidget />` flotante en la **esquina inferior izquierda** (la esquina inferior derecha se reserva para el botón flotante de WhatsApp).
- Guarda preferencias en `localStorage` (`a11y_settings`):
  1. **Modo Claro / Modo Oscuro** (detecta `prefers-color-scheme` la primera vez).
  2. **Tamaño de Texto:** 4 niveles (`90%`, `100%`, `115%`, `130%`) aplicados al `font-size` de `<html>` para escalar toda la interfaz basada en `rem`.
  3. **Alto Contraste** (`a11y-high-contrast`).
  4. **Escala de Grises** (`a11y-grayscale`).
  5. **Resaltar Enlaces y Botones** (`a11y-highlight-links`).
  6. **Pausar Animaciones** (`a11y-reduce-motion`).
  7. **Restablecer Configuración**.

---

## 6. Esquema Completo de Base de Datos (MySQL / MariaDB)

1. **`users`**: Estándar Laravel + `role` (`super_admin`, `manager`).
2. **`site_settings`**:
   - `id`, `brand_name`, `tagline`, `seo_description`
   - `logo_light_path`, `logo_dark_path`, `favicon_path`
   - `color_primary` (default `#6D28D9`), `color_secondary` (default `#0F172A`), `color_accent` (default `#F59E0B`)
   - `whatsapp_number`, `phone_number`, `contact_email`, `address_text`
   - `facebook_url`, `instagram_url`, `tiktok_url`, `youtube_url`, `spotify_url`
   - `online_discount_active` (bool), `online_discount_type` (`percentage`|`fixed`), `online_discount_value` (decimal 10,2), `online_discount_min_amount` (decimal 10,2)
   - `quote_validity_days` (int, default `7`), `quote_terms_text` (text)
   - `hero_title`, `hero_subtitle`, `hero_video_url` (nullable)
   - `timestamps`
3. **`categories`**:
   - `id`, `name`, `slug` (unique), `description` (nullable), `icon` (string, nullable), `sort_order` (int), `is_active` (bool), `timestamps`
4. **`services`**:
   - `id`, `category_id` (FK), `name`, `slug` (unique), `short_description`, `full_description` (longText)
   - `service_type` (enum: `core_band`, `ceremony`, `soloist`, `partner_act`, `addon_extra`)
   - `base_price` (decimal 10,2), `price_unit` (string, ej. `"por evento (5 hrs)"`, `"por hora"`, `"por pieza"`)
   - `daily_capacity` (unsigned int, default `1`)
   - `is_featured` (bool), `is_active` (bool), `sort_order` (int), `timestamps`
5. **`service_features`**:
   - `id`, `service_id` (FK cascade), `feature_text` (string), `sort_order` (int)
6. **`service_media`**:
   - `id`, `service_id` (FK cascade), `media_type` (`image`|`video_local`|`video_embed`), `file_path` (nullable), `embed_url` (nullable), `title` (nullable), `is_cover` (bool), `sort_order` (int), `timestamps`
7. **`coverage_zones`**:
   - `id`, `city_name`, `state_name`, `zone_type` (`local_hq`|`foranea`|`custom_quote`), `travel_fee_type` (`fixed`|`percentage`), `travel_fee_value` (decimal 10,2), `notes` (nullable), `is_active` (bool), `sort_order` (int), `timestamps`
8. **`quotes`**:
   - `id`, `folio` (unique), `client_name`, `client_email`, `client_phone`
   - `event_date` (date), `event_time` (time, nullable), `event_type` (string), `venue_name` (nullable)
   - `coverage_zone_id` (FK nullable), `custom_city_name` (nullable)
   - `subtotal` (decimal 10,2), `travel_fee` (decimal 10,2), `discount_amount` (decimal 10,2), `total` (decimal 10,2)
   - `valid_until` (date), `status` (`new`|`contacted`|`confirmed`|`cancelled`, default `new`)
   - `admin_notes` (text, nullable), `pdf_path` (nullable), `timestamps`
9. **`quote_items`**:
   - `id`, `quote_id` (FK cascade), `service_id` (FK nullOnDelete), `service_name_snapshot` (string), `quantity` (int), `unit_price` (decimal 10,2), `line_total` (decimal 10,2)
10. **`events`**:
    - `id`, `quote_id` (FK nullable), `title`, `event_date` (date), `start_time` (time, nullable), `end_time` (time, nullable)
    - `city_name`, `venue_name` (nullable), `client_name`, `client_phone`
    - `agreed_total` (decimal 10,2), `deposit_paid` (decimal 10,2, default `0`), `status` (`reserved`|`completed`|`cancelled`, default `reserved`)
    - `internal_notes` (text, nullable), `timestamps`
11. **`event_services`** (Pivote para control de capacidad):
    - `id`, `event_id` (FK cascade), `service_id` (FK cascade), `quantity` (int, default `1`)

---

## 7. Guía de Implementación Paso a Paso para Claude Code

Ejecuta estas 5 fases en orden estricto. No pases a la siguiente fase sin asegurarte de que la anterior compila sin errores y tiene sus pruebas/seeders funcionales.

### FASE 1: Inicialización del Proyecto, Dependencias y Base de Datos
1. Instala Laravel 11, Inertia.js (`inertiajs/inertia-laravel`), React (`@inertiajs/react`, `react`, `react-dom`), Tailwind CSS, `lucide-react` y `barryvdh/laravel-dompdf`.
2. Configura Vite (`vite.config.js`) para compilar React cleanly en `public/build`.
3. Crea todas las migraciones del punto 6 en orden referencial correcto.
4. Crea todos los modelos Eloquent con sus relaciones (`hasMany`, `belongsTo`, `belongsToMany`), casts (`decimal:2`, `boolean`, `date`) y observer/evento en `SiteSetting` para limpiar caché (`Cache::forget('site_settings')`).
5. Crea un `DatabaseSeeder` rico y realista en Español de México que incluya:
   - Usuario admin (`admin@colectivomusical.mx` / `password`).
   - `SiteSetting` inicial con datos base para Toluca y 10% de descuento en línea.
   - `CoverageZones`: *Toluca / Metepec* (`local_hq`, `$0`), *CDMX* (`foranea`, `$2,500`), *Valle de Bravo* (`foranea`, `$2,000`), *Querétaro* (`foranea`, `$3,500`), *Otra ciudad* (`custom_quote`, `$0`).
   - `Categories`: *Grupo Musical Versátil*, *Ceremonias y Cuerdas*, *Solistas y Duetos*, *Mariachi, Banda y Regional*, *Audio, Iluminación y Show*.
   - Al menos 8 servicios realistas con precios transparentes, `daily_capacity` configurado y múltiples `service_features` por cada uno.

### FASE 2: Motor White-Label, Accesibilidad Universal y Layouts
1. Configura `HandleInertiaRequests` para compartir globalmente: `siteSettings` (cacheado), `auth.user`, y mensajes flash.
2. En `resources/views/app.blade.php`:
   - Inyecta dinámicamente el `<title>`, meta descripción SEO y `<link rel="icon">` usando `$siteSettings->favicon_path`.
   - Inyecta las variables CSS `--color-primary`, `--color-secondary`, `--color-accent` en `:root` y configúralas en `tailwind.config.js`.
3. Crea `<AccessibilityWidget />` en React con persistencia en `localStorage`, modo claro/oscuro, escala tipográfica, alto contraste, escala de grises, resaltado de enlaces y reducción de movimiento.
4. Construye dos layouts profesionales y responsivos:
   - `PublicLayout.jsx`: Navbar transparente/sticky con logo dinámico (cambia entre `logo_light` y `logo_dark` según el tema activo), navegación limpia (Inicio, Servicios, Paquetes, Cotizador en Línea, Contacto), botón CTA destacado al Cotizador, Footer completo con aviso de privacidad, `<AccessibilityWidget />` (izquierda) y botón flotante de WhatsApp (derecha).
   - `AdminLayout.jsx`: Sidebar moderno colapsable con acceso a Dashboard, Identidad y Marca, Catálogo (Categorías y Servicios), Zonas y Viáticos, Cotizaciones (CRM) y Agenda de Eventos.

### FASE 3: Panel Administrativo (CMS, Zonas y Chunked Video Uploader)
1. **Identidad y Apariencia (`Admin/Settings/Edit.jsx`):** Formulario con pestañas (Marca y Logos, Colores, Contacto y Redes, Reglas del Cotizador y Descuento Web) con vista previa en vivo.
2. **CRUD de Zonas de Cobertura (`Admin/Zones/Index.jsx`):** Gestión de ciudades, tipos de sede y tarifas de viáticos.
3. **CRUD de Categorías y Servicios (`Admin/Services/...`):** Creación/edición de servicios, gestión dinámica de lista de características (`service_features`) y configuración de `daily_capacity`.
4. **Subida de Videos por Bloques (`ChunkedVideoUploader.jsx` + `MediaChunkController.php`):**
   - Divide archivos de video en fragmentos de 2MB en el navegador.
   - Envía cada chunk vía Axios con barra de progreso real (`0%` a `100%`) a `/admin/media/upload-chunk`.
   - El backend almacena temporalmente en `storage/app/chunks/{uploadId}` y al completar ensambla en `storage/app/public/media/videos/`, eliminando temporales.
   - Permite también subir imágenes optimizadas y pegar URLs de YouTube/Vimeo.

### FASE 4: Sitio Público, Cotizador Interactivo y Generación de PDF
1. **Home Page (`Public/Home.jsx`):** Hero section cinematográfico de alto impacto para eventos VIP, barra de confianza, categorías destacadas, paquetes estrella con precios transparentes, sección de tipos de eventos (Bodas, XV Años, Empresariales), sección de repertorio/playlists y llamado a la acción hacia el cotizador.
2. **Explorador de Catálogo y Detalle de Servicio (`Public/Services/Index.jsx` y `Show.jsx`):** Filtros por categoría, galería de fotos/videos en vivo, lista de lo que incluye y botón *"Agregar a mi cotización"*.
3. **Cotizador Interactivo (`Public/QuoteBuilder.jsx`) + `AvailabilityService` + `QuoteCalculatorService`:**
   - Paso 1: Selecciona fecha del evento y ciudad/zona (consulta disponibilidad real de servicios para esa fecha y calcula viáticos).
   - Paso 2: Arma tu evento agregando servicios principales y amenidades/extras con contador de cantidades/horas; muestra barra flotante o lateral con el desglose y el **ahorro acumulado por cotizar en línea**.
   - Paso 3: Formulario de captura de datos del cliente para desbloquear y descargar el PDF.
4. **Generación de PDF y Notificaciones (`QuotePdfService.php`):**
   - Plantilla Blade `resources/views/pdf/quote.blade.php` elegante y limpia compatible con DomPDF.
   - Envío de correo al cliente con el PDF adjunto y notificación al administrador.
   - Pantalla de confirmación con descarga directa del PDF y botón de WhatsApp con mensaje prellenado con el folio.

### FASE 5: Mini-CRM de Cotizaciones y Agenda Multi-Evento
1. **Gestión de Cotizaciones (`Admin/Quotes/Index.jsx` y `Show.jsx`):**
   - Tabla con filtros por estatus, búsqueda por folio/cliente, vista detallada de partidas, cambio de estatus, notas internas, descarga de PDF y enlace directo a WhatsApp.
   - Botón **"Confirmar y Agendar Evento"**: Al confirmar una cotización, abre un modal prellenado (fecha, horarios, cliente, servicios, total y campo para registrar anticipo recibido) que crea el `Event` y sus `event_services` asociados.
2. **Agenda / Calendario Operativo (`Admin/Events/Index.jsx`):**
   - Vista de calendario mensual y lista cronológica.
   - Soporte para **múltiples eventos en un mismo día**, mostrando claramente qué servicios están asignados en cada fecha y alertando si algún servicio alcanza o excede su `daily_capacity`.
   - Permite también crear eventos manuales directos desde el panel.

---

## 8. Estándares de Calidad y Entrega
- Código limpio, tipado con DocBlocks donde corresponda, sin warnings de consola en React y sin consultas N+1 en Laravel (`with()`).
- Todos los textos visibles al usuario en **Español de México** impecable, persuasivo y orientado a conversión.
- Verifica que `npm run build` compile sin errores para garantizar despliegue inmediato en DonWeb / Ferozo.