<#
.SYNOPSIS
    Despliega el proyecto al hosting compartido (Ferozo/DonWeb) por FTP/FTPS.

.DESCRIPTION
    1. Lee las credenciales de .env.deploy (nunca imprime la contraseña).
    2. Ejecuta "npm run build" y se detiene si falla.
    3. Arma una copia limpia de producción en .deploy\release:
       app, bootstrap, config, database\production_schema.sql, lang, public,
       resources, routes, estructura de storage, vendor (sin dependencias de
       desarrollo), artisan, composer.json y composer.lock.
       Excluye node_modules, .git, tests, .env, .env.deploy, database.sqlite,
       cachés locales y los archivos subidos de public\uploads (conserva carpetas,
       .gitkeep y .htaccess).
    4. Sube solo los archivos que cambiaron (manifiesto local) por FTPS explícito.

.PARAMETER DryRun
    Arma la copia y muestra qué se subiría, sin conectarse al servidor.

.PARAMETER Full
    Ignora el manifiesto y vuelve a subir todos los archivos.

.PARAMETER SkipBuild
    Omite "npm run build" (usar solo si acabas de compilar).

.PARAMETER Yes
    No pide confirmación antes de subir.

.PARAMETER EnvPath
    Archivo de credenciales alterno (por defecto .env.deploy).

.EXAMPLE
    .\deploy.ps1 -DryRun
    .\deploy.ps1
#>
[CmdletBinding()]
param(
    [switch]$DryRun,
    [switch]$Full,
    [switch]$SkipBuild,
    [switch]$Yes,
    [string]$EnvPath = '.env.deploy'
)

$ErrorActionPreference = 'Stop'
$ProjectRoot = $PSScriptRoot
$WorkDir = Join-Path $ProjectRoot '.deploy'
$Release = Join-Path $WorkDir 'release'
$EnvFile = $EnvPath
if (-not [System.IO.Path]::IsPathRooted($EnvFile)) { $EnvFile = Join-Path $ProjectRoot $EnvPath }

function Write-Step([string]$Message) {
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Stop-Deploy([string]$Message) {
    Write-Host ''
    Write-Host "ERROR: $Message" -ForegroundColor Red
    exit 1
}

function Read-DeployEnv([string]$Path) {
    $vars = @{}
    foreach ($line in Get-Content -LiteralPath $Path -Encoding UTF8) {
        $trimmed = $line.Trim()
        if ($trimmed -eq '' -or $trimmed.StartsWith('#')) { continue }
        $index = $trimmed.IndexOf('=')
        if ($index -lt 1) { continue }
        $key = $trimmed.Substring(0, $index).Trim()
        $value = $trimmed.Substring($index + 1).Trim()
        if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        $vars[$key] = $value
    }
    return $vars
}

function Copy-Tree([string]$Source, [string]$Destination, [string[]]$ExcludeDirs = @(), [string[]]$ExcludeFiles = @(), [string]$Filter = '*') {
    $arguments = @($Source, $Destination, $Filter, '/E', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/R:1', '/W:1')
    if ($ExcludeDirs.Count -gt 0) { $arguments += '/XD'; $arguments += $ExcludeDirs }
    if ($ExcludeFiles.Count -gt 0) { $arguments += '/XF'; $arguments += $ExcludeFiles }
    & robocopy @arguments | Out-Null
    # Robocopy: códigos menores a 8 significan éxito.
    if ($LASTEXITCODE -ge 8) { Stop-Deploy "No se pudo copiar $Source (robocopy $LASTEXITCODE)." }
    $global:LASTEXITCODE = 0
}

function Get-ShortHash([string]$Text) {
    $sha = [System.Security.Cryptography.SHA1]::Create()
    $bytes = $sha.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($Text))
    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '').Substring(0, 12)
}

Set-Location -LiteralPath $ProjectRoot

# ------------------------------------------------------------------ 1. Credenciales
Write-Step 'Leyendo configuración de despliegue'
$deploy = @{}
if (Test-Path -LiteralPath $EnvFile) {
    $deploy = Read-DeployEnv $EnvFile
} elseif (-not $DryRun) {
    Stop-Deploy 'No existe .env.deploy. Cópialo desde .env.deploy.example y completa tus datos.'
}

if (-not $DryRun) {
    foreach ($key in @('FTP_HOST', 'FTP_USER', 'FTP_PASSWORD', 'FTP_REMOTE_PATH')) {
        if (-not $deploy.ContainsKey($key) -or [string]::IsNullOrWhiteSpace($deploy[$key])) {
            Stop-Deploy "Falta $key en .env.deploy."
        }
    }
}

$secure = $true
if ($deploy.ContainsKey('FTP_SECURE') -and $deploy['FTP_SECURE'].ToLower() -eq 'false') { $secure = $false }
$rootHtaccess = $deploy.ContainsKey('DEPLOY_ROOT_HTACCESS') -and $deploy['DEPLOY_ROOT_HTACCESS'].ToLower() -eq 'true'
$port = '21'
if ($deploy.ContainsKey('FTP_PORT') -and $deploy['FTP_PORT']) { $port = $deploy['FTP_PORT'] }

$maskedUser = '(sin definir)'
if ($deploy['FTP_USER']) { $maskedUser = $deploy['FTP_USER'].Substring(0, [Math]::Min(2, $deploy['FTP_USER'].Length)) + '***' }
$mode = 'FTP sin cifrar'
if ($secure) { $mode = 'FTPS explícito (TLS)' }

Write-Host "  Servidor : $($deploy['FTP_HOST']):$port"
Write-Host "  Usuario  : $maskedUser"
Write-Host "  Destino  : $($deploy['FTP_REMOTE_PATH'])"
Write-Host "  Conexión : $mode"
Write-Host "  .htaccess raíz: $rootHtaccess"
if (-not $secure) { Write-Host '  ADVERTENCIA: FTP_SECURE=false envía la contraseña sin cifrar.' -ForegroundColor Yellow }

# ------------------------------------------------------------------ 2. Build
if ($SkipBuild) {
    Write-Step 'Omitiendo npm run build (-SkipBuild)'
} else {
    Write-Step 'Compilando assets (npm run build)'
    & npm run build
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'npm run build falló. Corrige los errores antes de desplegar.' }
}
if (-not (Test-Path -LiteralPath (Join-Path $ProjectRoot 'public\build\manifest.json'))) {
    Stop-Deploy 'No existe public\build\manifest.json. Ejecuta npm run build.'
}

# ------------------------------------------------------------------ 3. Copia de producción
Write-Step 'Preparando copia limpia de producción en .deploy\release'
if (Test-Path -LiteralPath $Release) { Remove-Item -LiteralPath $Release -Recurse -Force }
New-Item -ItemType Directory -Path $Release | Out-Null

foreach ($dir in @('app', 'config', 'lang', 'resources', 'routes', 'vendor')) {
    Copy-Tree (Join-Path $ProjectRoot $dir) (Join-Path $Release $dir)
}
Copy-Tree (Join-Path $ProjectRoot 'bootstrap') (Join-Path $Release 'bootstrap') @() @('packages.php', 'services.php', 'config.php', 'routes-v7.php', 'events.php')
Copy-Tree (Join-Path $ProjectRoot 'public') (Join-Path $Release 'public') @((Join-Path $ProjectRoot 'public\storage'), (Join-Path $ProjectRoot 'public\uploads')) @('hot')
# public\uploads: solo carpetas, .gitkeep y .htaccess (nunca archivos subidos de prueba).
Copy-Tree (Join-Path $ProjectRoot 'public\uploads') (Join-Path $Release 'public\uploads') @() @() '.gitkeep'
Copy-Tree (Join-Path $ProjectRoot 'public\uploads') (Join-Path $Release 'public\uploads') @() @() '.htaccess'
# storage: solo la estructura de carpetas (sin logs, sesiones, cachés ni fragmentos).
Copy-Tree (Join-Path $ProjectRoot 'storage') (Join-Path $Release 'storage') @((Join-Path $ProjectRoot 'storage\inertia-devtools'), (Join-Path $ProjectRoot 'storage\app\chunks')) @() '.gitignore'

New-Item -ItemType Directory -Path (Join-Path $Release 'database') | Out-Null
foreach ($file in @('artisan', 'composer.json', 'composer.lock', 'database\production_schema.sql')) {
    Copy-Item -LiteralPath (Join-Path $ProjectRoot $file) -Destination (Join-Path $Release $file)
}
if ($rootHtaccess) {
    Copy-Item -LiteralPath (Join-Path $ProjectRoot 'deploy\root.htaccess') -Destination (Join-Path $Release '.htaccess')
}

Write-Step 'Quitando dependencias de desarrollo de vendor (composer install --no-dev)'
& php (Join-Path $ProjectRoot 'composer.phar') install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress --working-dir="$Release"
if ($LASTEXITCODE -ne 0) { Stop-Deploy 'composer install --no-dev falló.' }

# Verificación de seguridad: nada sensible ni de desarrollo en la copia.
$forbidden = Get-ChildItem -LiteralPath $Release -Recurse -Force -File | Where-Object {
    $relative = $_.FullName.Substring($Release.Length)
    ($relative -notmatch '^\\vendor\\') -and (($_.Name -like '.env*') -or ($_.Name -eq 'database.sqlite') -or ($relative -match '\\(node_modules|\.git|tests)\\'))
}
if ($forbidden) {
    $forbidden | Select-Object -First 5 | ForEach-Object { Write-Host "  $($_.FullName)" -ForegroundColor Red }
    Stop-Deploy 'La copia de producción contiene archivos que no deben subirse.'
}
$uploadsLeak = Get-ChildItem -LiteralPath (Join-Path $Release 'public\uploads') -Recurse -Force -File | Where-Object { $_.Name -ne '.gitkeep' -and $_.Name -ne '.htaccess' }
if ($uploadsLeak) { Stop-Deploy 'public\uploads contiene archivos subidos; no deben desplegarse.' }

$fileCount = (Get-ChildItem -LiteralPath $Release -Recurse -Force -File).Count
Write-Host "  Copia lista: $fileCount archivos."

# ------------------------------------------------------------------ 4. Subida
$manifestName = 'manifest-local.json'
if ($deploy['FTP_HOST']) { $manifestName = 'manifest-' + (Get-ShortHash ("$($deploy['FTP_HOST'])|$($deploy['FTP_REMOTE_PATH'])")) + '.json' }
$manifest = Join-Path $WorkDir $manifestName

$syncArgs = @((Join-Path $ProjectRoot 'deploy\ftp-sync.php'), $Release, $manifest)
if ($DryRun) { $syncArgs += '--dry-run' }
if ($Full) { $syncArgs += '--full' }

if (-not $DryRun -and -not $Yes) {
    Write-Host ''
    Write-Host "Se actualizará el sitio en vivo en $($deploy['FTP_HOST'])$($deploy['FTP_REMOTE_PATH'])." -ForegroundColor Yellow
    $answer = Read-Host 'Escribe DESPLEGAR para continuar'
    if ($answer -cne 'DESPLEGAR') { Stop-Deploy 'Despliegue cancelado.' }
}

if ($DryRun) { Write-Step 'Simulación (no se conecta al servidor)' } else { Write-Step "Subiendo por $mode" }

# Las credenciales viajan por variables de entorno del proceso (no por argumentos visibles).
$envKeys = @('FTP_HOST', 'FTP_PORT', 'FTP_USER', 'FTP_PASSWORD', 'FTP_REMOTE_PATH', 'FTP_SECURE')
try {
    foreach ($key in $envKeys) {
        if ($deploy.ContainsKey($key)) { Set-Item -Path "Env:$key" -Value $deploy[$key] }
    }
    & php @syncArgs
    $syncExit = $LASTEXITCODE
} finally {
    foreach ($key in $envKeys) { Remove-Item -Path "Env:$key" -ErrorAction SilentlyContinue }
}
if ($syncExit -ne 0) { Stop-Deploy 'La subida no terminó. Vuelve a ejecutar .\deploy.ps1: continuará donde se quedó.' }

Write-Step 'Despliegue terminado'
if (-not $DryRun) {
    Write-Host '  Si este despliegue incluye migraciones nuevas, aplícalas en el servidor (ver README.md).'
    Write-Host '  Si agregaste permisos en config\permissions.php, ejecuta php artisan permissions:sync en el servidor.'
}
