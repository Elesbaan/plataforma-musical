# AGENTS.md — Gobernanza Técnica, Calidad y Flujo de Desarrollo

Este documento define las reglas obligatorias de arquitectura, calidad, diseño responsivo, pruebas y control de versiones para este proyecto (**Laravel 11 + Inertia.js + React + Tailwind CSS**). Complementa y gobierna la ejecución técnica de `CLAUDE_MASTER_SPEC.md`.

---

## 0. Precedencia de Instrucciones y Reglas de Oro (CRÍTICO)

Cuando dos fuentes entren en conflicto, aplica este orden estricto:
1. Instrucción explícita y alcance de la solicitud actual del usuario.
2. Este archivo `AGENTS.md` (reglas de calidad, arquitectura y restricciones de servidor).
3. `CLAUDE_MASTER_SPEC.md` (reglas de negocio, esquema de datos y roadmap).
4. Código existente y configuración del repositorio (`composer.json`, `package.json`, `vite.config.js`).
5. Convenciones generales de Laravel / React.

### Prohibición de Inventar o Hardcodear (CRÍTICO)
- **Cero datos quemados de negocio:** Está estrictamente prohibido hardcodear en JSX o controladores nombres de marca, teléfonos, correos, ciudades, precios, porcentajes de descuento o categorías. Todo debe consumirse dinámicamente desde `SiteSetting`, `CoverageZone`, `Category` y `Service`.
- **No asumir contratos:** Si falta un campo, regla o comportamiento no definido en `CLAUDE_MASTER_SPEC.md` ni en las migraciones existentes, inspecciona el código real antes de asumir; si persiste ambigüedad funcional, detente y pregunta al usuario.

---

## 1. Restricciones de Infraestructura (Hosting Compartido Ferozo / DonWeb)

1. **Entorno 100% PHP en Producción:** No usar procesos Node.js persistentes (`ssr` de Inertia desactivado), ni Redis, ni Horizon, ni Docker. Las colas (`QUEUE_CONNECTION`) deben funcionar de forma síncrona (`sync`) o mediante base de datos (`database`) procesable vía Cron Job estándar (`php artisan schedule:run`).
2. **Assets Compilados:** El frontend en React se compila con Vite (`npm run build`) hacia `public/build/`. Toda dependencia de iconos (`lucide-react`), fuentes o librerías UI debe instalarse vía `npm` (prohibido depender de CDNs externos que puedan fallar o bloquearse).
3. **Subida de Videos por Bloques (Chunked Upload):** Nunca procesar videos en un solo `POST` monolítico. Mantener estrictamente el flujo por fragmentos (`/admin/media/upload-chunk`) con ensamblado en backend y limpieza de temporales para esquivar límites de `upload_max_filesize` y `post_max_size`.
4. **Archivos Públicos sin Symlinks:** Prohibido depender de `php artisan storage:link`. Todo archivo público subido (logos, favicon, fotos y videos ensamblados) se guarda en `public/uploads/...` mediante el disco `uploads` y `App\Services\UploadService` (ver sección 9).

---

## 2. Arquitectura y Separación de Responsabilidades

### Backend (Laravel 11)
- **Controladores Delgados (Thin Controllers):** Los controladores solo reciben la petición, delegan validación a un `FormRequest`, invocan una clase de Servicio (`app/Services/*`) y retornan una respuesta Inertia (`Inertia::render(...)`) o redirección con mensaje flash.
- **Lógica de Negocio Centralizada en Servicios:**
  - `App\Services\QuoteCalculatorService`: Único responsable de calcular subtotales, viáticos por `CoverageZone`, descuentos web configurables y totales. Prohibido duplicar fórmulas financieras en controladores.
  - `App\Services\AvailabilityService`: Único responsable de calcular cupos ocupados vs `daily_capacity` por fecha.
  - `App\Services\QuotePdfService`: Generación y almacenamiento de PDFs con `barryvdh/laravel-dompdf`.
- **Seguridad y Validación:** Toda mutación (`POST`, `PUT`, `PATCH`, `DELETE`) valida estrictamente en backend mediante `FormRequest`, incluyendo recálculo de precios y verificación de disponibilidad en tiempo real antes de guardar.

### Frontend (React + Inertia.js + Tailwind CSS)
- **Sin Lógica de Negocio en Vistas:** Los componentes de React (`resources/js/Pages/**`) solo presentan datos recibidos por props de Inertia, manejan estado local de UI (modales, pasos del cotizador, filtros) y envían formularios mediante `useForm` de Inertia.
- **Reutilización Antes de Crear:** Antes de crear un componente nuevo, revisa `resources/js/Components/**` y `resources/js/Layouts/**`. Extiende componentes existentes mediante `props` o variantes en lugar de duplicar botones, tarjetas, modales o inputs.
- **Motor de Identidad Dinámica (Theming):** Nunca hardcodees colores de marca (`bg-purple-600`, `text-amber-500`) en componentes de producto. Usa las variables CSS inyectadas desde `SiteSetting` (`--color-primary`, `--color-secondary`, `--color-accent`) mapeadas en Tailwind.
- **Lenguaje de Interfaz:** Todo texto visible al usuario final (etiquetas, botones, mensajes de error, validaciones, correos, PDFs) debe redactarse en **español claro, profesional y directo** (trato de "tú" en el sitio público y panel administrativo). Prohibido mostrar jerga técnica (`null`, `payload`, `exception`, `ID`) en pantalla.

---

## 3. Diseño Responsivo y Accesibilidad Universal (OBLIGATORIO)

### Mobile-First Estricto
- **Diseño Responsivo Universal:** Ninguna vista pública ni del panel administrativo se considera terminada si presenta desbordamiento horizontal (`overflow-x`), textos cortados o botones inaccesibles en pantallas móviles (`360px` a `430px`).
- **Adaptación de Estructuras Complejas:**
  - En el **Cotizador Interactivo**, en móvil debe mostrarse un resumen flotante colapsable en la parte inferior con el total estimado en tiempo real, evitando que el usuario tenga que hacer scroll infinito para ver cuánto lleva cotizado.
  - En el **Panel Administrativo**, las tablas de cotizaciones, servicios y agenda deben adaptarse en móvil mediante tarjetas apiladas o contenedores con scroll horizontal controlado (`overflow-x-auto`) y acciones táctiles accesibles (`min-h-[44px]`).
  - La navegación móvil debe usar un menú colapsable limpio y accesible por teclado.

### Panel de Accesibilidad WCAG (Persistencia en `localStorage`)
- El componente `<AccessibilityWidget />` debe mantenerse activo en el layout público con persistencia en `localStorage` (`a11y_preferences`):
  - Modo Claro / Modo Oscuro (`dark` en `<html>`).
  - Escalado tipográfico (`A-`, `A`, `A+`, `A++`) ajustando el tamaño base en `html` para escalar todo el layout basado en `rem`.
  - Alto contraste (`a11y-high-contrast`), escala de grises (`a11y-grayscale`), resaltado de enlaces/botones (`a11y-highlight-links`) y reducción de movimiento (`a11y-reduce-motion`).

---

## 4. Análisis de Impacto Antes de Editar

Antes de modificar modelos, migraciones, servicios compartidos o componentes base en `resources/js/Components/**`:
1. Identifica qué páginas, controladores y pruebas consumen ese archivo.
2. Mantén el alcance acotado a la tarea solicitada; no realices refactorizaciones masivas no autorizadas sobre código funcional existente.
3. Si modificas una tabla o contrato de props de Inertia, actualiza en el mismo cambio todos los consumidores afectados y sus pruebas.

---

## 5. Pruebas Automatizadas Obligatorias al Terminar (OBLIGATORIO)

**Ninguna tarea o módulo se declara terminado sin ejecutar y aprobar pruebas reales.**

1. **Pruebas Backend (Pest / PHPUnit):**
   - Toda funcionalidad crítica nueva o modificada debe contar con pruebas automatizadas en `tests/Feature/` o `tests/Unit/` que cubran:
     - **Cotizador y Cálculos (`QuoteCalculatorTest`):** cálculo correcto de partidas, suma de viáticos por `CoverageZone`, aplicación de descuento web (porcentaje y monto fijo, respeto de monto mínimo) y validación contra manipulación de precios desde el cliente.
     - **Disponibilidad y Capacidad (`AvailabilityTest`):** bloqueo automático de un servicio cuando los eventos confirmados (`reserved`) igualan su `daily_capacity` en una fecha específica, y liberación cuando se consulta otra fecha.
     - **Flujo de Cotización y PDF (`QuoteSubmissionTest`):** validación de campos obligatorios, generación de folio único `COT-YYYY-XXXX`, creación de archivo PDF y persistencia en base de datos.
     - **Panel Admin (`AdminCrudTest`):** protección por autenticación, actualización de `SiteSetting` con invalidación de caché, y CRUD de servicios/zonas.
   - Ejecutar `php artisan test` antes de cerrar cualquier entrega y reportar el resultado exacto (pruebas ejecutadas y aprobadas).
2. **Verificación de Build Frontend:**
   - Ejecutar `npm run build` después de cada cambio en React/Tailwind/Inertia para garantizar cero errores de compilación, imports rotos o problemas de empaquetado para producción.

---

## 6. Protocolo de Git y Commits Atómicos (OBLIGATORIO)

1. **Commits Frecuentes y Atómicos:** Al terminar cada módulo, funcionalidad o fase verificada (con `php artisan test` y `npm run build` en verde), realiza un commit limpio en Git con mensajes convencionales en español:
   - `feat(cotizador): implementar calculo en tiempo real y validacion de viaticos`
   - `feat(admin): agregar CRUD de servicios con capacidad diaria configurable`
   - `feat(ui): implementar widget de accesibilidad universal y modo oscuro`
   - `test(quotes): agregar pruebas de disponibilidad por fecha y descuento web`
   - `fix(media): corregir ensamblado final en subida de videos por bloques`
2. **Nunca incluir archivos sensibles o pesados:** Verifica que `.env`, `/vendor`, `/node_modules` y archivos temporales de chunks (`storage/app/chunks/*`) estén ignorados en `.gitignore`.
3. **Control de Push:** Realiza los `git commit` localmente al concluir cada entregable validado, pero **no ejecutes `git push` sin autorización explícita del usuario**, salvo que el usuario te indique hacer push al terminar la sesión.

---

## 7. Checklist de Cierre de Cada Tarea (Reportar al Usuario)

Al finalizar cada instrucción o fase, entrega un resumen breve con evidencia real:
1. **Archivos creados/modificados** y propósito.
2. **Verificación Responsiva:** Confirmación de adaptación móvil (`360px+`) y escritorio en las vistas tocadas.
3. **Resultado de Pruebas:** Salida resumida de `php artisan test` y `npm run build`.
4. **Commit realizado:** Hash y mensaje del commit creado en Git.

## 8. Script SQL Maestro de Producción (`database/production_schema.sql`) — OBLIGATORIO

Dado que el proyecto se desplegará en un entorno compartido (Ferozo / DonWeb) donde la base de datos inicial se importa vía phpMyAdmin o cliente MySQL estándar, **debes mantener siempre actualizado un único archivo SQL maestro** en `database/production_schema.sql`.

> **Generación automática:** el archivo se regenera con `php artisan db:export-production-sql` (a partir de las migraciones y el `DatabaseSeeder`). No lo edites a mano: la prueba `ProductionSchemaSqlTest` falla si queda desincronizado.

### Reglas Estrictas del Archivo SQL Maestro
1. **Sincronización Continua con Migraciones y Seeders:**
   - Cada vez que crees o modifiques una migración o un seeder de catálogos iniciales, debes actualizar inmediatamente `database/production_schema.sql` en el mismo commit.
2. **Edición Limpia desde el `CREATE TABLE` (Prohibido acumular `ALTER TABLE` de desarrollo):**
   - Si durante el desarrollo cambia la estructura de una tabla ya registrada en `database/production_schema.sql` (nuevas columnas, cambio de tipo de dato, índices o llaves foráneas), **edita directamente la sentencia `CREATE TABLE` original** dentro del archivo. Nunca agregues parches `ALTER TABLE` al final del script; el archivo debe representar siempre una instalación limpia desde cero.
3. **Compatibilidad Total con MySQL 8.0 / MariaDB 10.6+:**
   - Usa sintaxis estándar compatible con ambos motores (`ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`).
   - Inicia el script con `SET FOREIGN_KEY_CHECKS = 0;` y ciérralo con `SET FOREIGN_KEY_CHECKS = 1;` para evitar errores de orden al importar.
   - Ordena las sentencias `CREATE TABLE` respetando las dependencias de llaves foráneas (`FOREIGN KEY`).
   - Incluye las tablas internas esenciales de Laravel necesarias para operar en producción (`users`, `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`, `migrations`).
4. **Datos Iniciales y Catálogos Listos para Operar (`INSERT INTO`):**
   - Al final del esquema, incluye los `INSERT INTO` correspondientes a:
     - Usuario administrador inicial (con contraseña hasheada con bcrypt y comentario indicando las credenciales por defecto).
     - Configuración base de `site_settings` (con valores predeterminados funcionales).
     - Zonas de cobertura iniciales en `coverage_zones` (ej. Toluca / Metepec como sede base sin viáticos, y ejemplos de zonas foráneas).
     - Categorías base en `categories` y servicios de muestra en `services` con sus `service_features` para que el sitio y el cotizador funcionen desde el primer segundo tras importar el `.sql`.
5. **Verificación en el Checklist de Cierre:**
   - Ninguna tarea que toque base de datos se considera terminada si `database/production_schema.sql` no refleja exactamente el estado actual de las tablas y catálogos.

---

## 9. Sesión del Panel y Almacenamiento de Archivos (OBLIGATORIO)

### Sesión y Acceso Administrativo
1. **Acceso:** el login responde en `/admin/login` y en `/login`. No existe la opción “Mantener sesión iniciada” (`remember`): está prohibido reintroducirla.
2. **Expiración:** `SESSION_LIFETIME=30` (minutos de inactividad) y `SESSION_EXPIRE_ON_CLOSE=true` (la sesión termina al cerrar el navegador). Los valores por defecto en `config/session.php` son los mismos.
3. **Temporizador de inactividad:** `AdminLayout.jsx` usa `useIdleLogout`, que toma la duración de `auth.sessionLifetime` (compartida por `HandleInertiaRequests`), muestra `<IdleSessionWarning />` 60 segundos antes y cierra la sesión con `reason=inactivity`. La actividad real envía como máximo un `POST /admin/session/keep-alive` por minuto. Nunca hardcodees la duración en el frontend.
4. **Sesión o CSRF expirado (419):** se redirige al login con un mensaje claro (`bootstrap/app.php`); las peticiones JSON reciben `419` con mensaje en español.
5. **Formulario de acceso:** mantener el botón de mostrar/ocultar contraseña con `aria-label` dinámico y el límite de 5 intentos de `LoginRequest`.

### Almacenamiento para Hosting Compartido (sin `storage:link`)
1. **Disco `uploads`:** raíz en `public/uploads` (`config/filesystems.php`). Carpetas: `branding/` (logos y favicon), `services/images/` (fotos optimizadas a WebP, máximo 1920 px) y `services/videos/` (videos ensamblados).
2. **Acceso único vía `App\Services\UploadService`:** guardar (`store`, `storeOptimizedImage`), eliminar (`delete`) y generar URLs (`UploadService::url()`, basado en `asset()` para funcionar en subcarpetas). Prohibido usar el disco `public` o `Storage::url()` para archivos subidos.
3. **Git:** los archivos subidos están ignorados (`/public/uploads/**`); solo se versionan las carpetas mediante `.gitkeep`.
4. **Fragmentos de video:** se guardan en el disco privado `chunks` (`storage/app/chunks/{uploadId}`), se eliminan al ensamblar y el comando programado `media:clean-chunks` borra los fragmentos abandonados (requiere el Cron `php artisan schedule:run`).
5. **Reemplazo y borrado:** al reemplazar o eliminar un archivo (logo, foto, video) se borra también el archivo físico anterior para no acumular basura en el hosting.

### Idioma de Validación
- `APP_LOCALE=es`. Los mensajes y nombres de campos viven en `lang/es/validation.php`; al crear un campo nuevo en un `FormRequest`, agrega su nombre legible en `attributes`.

---

## 10. Perfiles, Permisos y Alcance Propio (RBAC) — OBLIGATORIO

1. **Fuente única de permisos:** `config/permissions.php` define módulos, claves de permiso y perfiles base. Para agregar un permiso: añádelo ahí, ejecuta `php artisan permissions:sync` (idempotente; también corre en la migración RBAC y en el `DatabaseSeeder`) y regenera el SQL maestro. Nunca escribas claves de permiso “sueltas” que no existan en el catálogo.
2. **Perfiles base (`is_system`):** Super Administrador (todos los permisos, siempre, vía `Gate::before`), Coordinador de Ventas y Proveedor Aliado. No se eliminan; sus permisos (salvo el Super Administrador) se ajustan desde la matriz del panel. Un perfil con usuarios asignados no se puede eliminar.
3. **Protección en backend (obligatoria):** cada ruta del panel exige su permiso con `can:<clave>` o una Policy (`can:update,service`). Ocultar opciones en React (`usePermissions`, `auth.permissions`) es solo presentación y nunca sustituye la verificación del backend. Usuarios inactivos (`is_active = false`) no inician sesión, son expulsados por el middleware `active` y no tienen ningún permiso.
4. **Alcance propio (`*_own`):** se limita a los servicios vinculados en `service_user`. `App\Policies\ServicePolicy` distingue `update` (servicio propio o `catalog.manage_all`) de `managePricing` (solo `catalog.manage_all`). Con alcance propio solo se editan descripciones, características, capacidad diaria y galería; los campos de `ServiceRequest::RESTRICTED_FIELDS` (precio, nombre, categoría, tipo, publicación, orden) están prohibidos y responden con error de validación. Cualquier listado o acción nueva sobre servicios, eventos o cotizaciones con permisos `*_own` debe filtrar por los servicios vinculados del usuario.
5. **Salvaguardas de usuarios:** nadie puede desactivarse, cambiar su propio perfil ni eliminarse, y siempre debe quedar al menos un Super Administrador activo (`UserAdminService`).
6. **Pruebas:** toda ruta nueva del panel debe tener una prueba que confirme el `403` para un perfil sin el permiso correspondiente (ver `RolePermissionTest` y `AccessControlTest`). En pruebas, crea usuarios con `User::factory()->superAdmin()` o `->withRole(Role::PARTNER)`.
