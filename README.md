# Plataforma de Colectivo Musical y Eventos

Sitio público, cotizador en línea con PDF, mini-CRM, agenda multi-evento y panel administrativo con perfiles y permisos.
**Laravel 11 + Inertia.js + React + Tailwind CSS**, pensado para hosting compartido (Ferozo/DonWeb) sin Node.js ni procesos persistentes en el servidor.

- Reglas técnicas y de calidad: [AGENTS.md](AGENTS.md)
- Especificación funcional: [CLAUDE_MASTER_SPEC.md](CLAUDE_MASTER_SPEC.md)

## Desarrollo local

```bash
php composer.phar install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run dev          # o npm run build
php artisan test
```

Acceso al panel: `/admin/login` (o `/login`) con `admin@colectivomusical.mx` / `password`.

---

## Despliegue a Ferozo/DonWeb

El despliegue se hace desde Windows con `deploy.ps1`, que compila, arma una copia limpia de producción y la sube por **FTPS explícito**. Solo sube los archivos que cambiaron desde el último despliegue.

### Requisitos en tu equipo

- PHP con las extensiones `ftp` y `openssl` (XAMPP las incluye), Node.js y npm.
- El archivo **`.env.deploy`** en la raíz del proyecto (cópialo desde [.env.deploy.example](.env.deploy.example)). Está en `.gitignore`: **nunca lo subas al repositorio ni lo compartas.**

| Variable | Descripción |
|---|---|
| `FTP_HOST`, `FTP_PORT` | Servidor FTP (puerto 21 para FTPS explícito). |
| `FTP_USER`, `FTP_PASSWORD` | Cuenta FTP. La contraseña nunca se imprime en consola. |
| `FTP_REMOTE_PATH` | Carpeta remota de la raíz del proyecto (donde quedará `artisan`), ej. `/public_html`. |
| `FTP_SECURE` | `true` (recomendado) para cifrar la conexión con TLS. |
| `DEPLOY_ROOT_HTACCESS` | Opcional. `true` si el dominio apunta a `FTP_REMOTE_PATH` y no a su carpeta `public/` (ver abajo). |

### Ejecutar un despliegue

```powershell
.\deploy.ps1 -DryRun     # simulación: arma la copia y lista lo que subiría, sin conectarse
.\deploy.ps1             # despliegue real (pide escribir DESPLEGAR para confirmar)
```

Opciones: `-Full` vuelve a subir todo (ignora el historial local), `-SkipBuild` omite `npm run build` y `-Yes` no pide confirmación.

Si PowerShell bloquea el script, ejecútalo con `powershell -ExecutionPolicy Bypass -File .\deploy.ps1`.

**Qué hace el script:**

1. Lee `.env.deploy` sin mostrar la contraseña.
2. Ejecuta `npm run build` y se detiene si falla.
3. Arma `.deploy\release` con: `app`, `bootstrap`, `config`, `database/production_schema.sql`, `lang`, `public`, `resources`, `routes`, la estructura de `storage`, `vendor` **sin dependencias de desarrollo** (`composer install --no-dev`), `artisan`, `composer.json` y `composer.lock`.
4. Excluye `node_modules`, `.git`, `tests`, `.env`, `.env.deploy`, `database.sqlite`, logs, sesiones, cachés locales y los archivos subidos de `public/uploads` (conserva carpetas, `.gitkeep` y `.htaccess`). Si detecta algo sensible en la copia, se detiene.
5. Sube cada archivo como `nombre.uploading` y luego lo renombra, para que el sitio nunca sirva un archivo a medio subir. Si la conexión falla, vuelve a ejecutar el script: continúa donde se quedó.

> El script **nunca sobrescribe** el `.env` del servidor, las fotos y videos subidos (`public/uploads`), los PDF de cotizaciones ni los logs.

> `ftp_ssl_connect` de PHP cifra la conexión, pero no valida el certificado del servidor. Despliega solo desde redes de confianza.

### Raíz web: `public/` vs `public_html`

- **Recomendado:** en el panel de Ferozo, apunta el dominio a `FTP_REMOTE_PATH/public` (ej. `/public_html/public`). Deja `DEPLOY_ROOT_HTACCESS=false`.
- **Si Ferozo solo permite `/public_html` como raíz:** usa `DEPLOY_ROOT_HTACCESS=true`. El script sube [deploy/root.htaccess](deploy/root.htaccess) como `.htaccess` raíz, que sirve todo desde `public/` (las URLs no muestran `/public`), bloquea archivos ocultos (`.env`) y responde 403 a `app/`, `vendor/`, `storage/`, `database/` y demás carpetas del proyecto.

Después del primer despliegue, verifica que `https://tudominio.mx/.env` y `https://tudominio.mx/vendor/` respondan **403 o 404**.

---

## Primera instalación en Ferozo (una sola vez)

1. **Base de datos:** en el panel de Ferozo crea una base MySQL/MariaDB y su usuario.
2. **Importar el esquema:** en phpMyAdmin selecciona la base e importa [database/production_schema.sql](database/production_schema.sql). Incluye todas las tablas, perfiles y permisos, la configuración inicial, las zonas, el catálogo de muestra y el usuario administrador.
3. **Raíz web:** configura el dominio como se explica arriba.
4. **Subir el proyecto:** ejecuta `.\deploy.ps1`. La primera vez sube unos 6,800 archivos (alrededor de 36 MB) y puede tardar varios minutos.
5. **Crear el `.env` del servidor:** con el administrador de archivos de Ferozo o tu cliente FTP, crea `.env` en `FTP_REMOTE_PATH` (junto a `artisan`) a partir de [deploy/env.production.example](deploy/env.production.example):
   - `APP_KEY`: genérala en tu equipo con `php artisan key:generate --show`.
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con tu dominio (https).
   - Datos de la base MySQL del paso 1 y de la cuenta SMTP para los correos de cotización.
6. **Permisos de escritura:** `storage/`, `bootstrap/cache/` y `public/uploads/` deben poder escribirse (755 o 775, según el hosting).
7. **Cron Job:** en Ferozo agrega una tarea programada **cada minuto** (confirma en el panel la ruta de PHP; suele ser `/usr/bin/php` o `/usr/local/bin/php`):
   ```
   * * * * * /usr/bin/php /home/TU_USUARIO/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
   Limpia diariamente los fragmentos de videos abandonados (`media:clean-chunks`).
8. **Primer acceso:** entra a `/admin/login` con `admin@colectivomusical.mx` / `password` y **cambia la contraseña de inmediato** (Usuarios → tu cuenta). Después completa **Identidad y marca**, sobre todo el `contact_email`, que recibe las notificaciones de cotizaciones.

---

## Despliegues posteriores

```powershell
.\deploy.ps1
```

`database/production_schema.sql` sirve solo para la **instalación inicial**. Cuando un despliegue incluye migraciones nuevas o permisos nuevos, hay que ejecutar en el servidor:

```bash
php artisan migrate --force
php artisan permissions:sync
```

- **Con acceso SSH:** ejecútalos desde la carpeta del proyecto.
- **Sin SSH:** crea en Ferozo una tarea Cron temporal (por ejemplo, para dentro de 2 minutos) con
  `/usr/bin/php /home/TU_USUARIO/public_html/artisan migrate --force && /usr/bin/php /home/TU_USUARIO/public_html/artisan permissions:sync`
  y **elimínala** en cuanto se ejecute.

Antes de migrar en producción, respalda la base desde phpMyAdmin (Exportar).
