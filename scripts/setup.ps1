$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))

function Invoke-Compose {
    & docker compose --project-directory $projectRoot -f (Join-Path $projectRoot 'compose.yaml') -p datashare @args
    if ($LASTEXITCODE -ne 0) { throw 'La commande Docker a échoué.' }
}

function New-LocalSecret {
    $bytes = [byte[]]::new(32)
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    return ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
}

$envPath = Join-Path $projectRoot '.env'
if (!(Test-Path -LiteralPath $envPath)) {
    $content = [IO.File]::ReadAllText((Join-Path $projectRoot '.env.example'))
    foreach ($placeholder in @('replace-with-a-local-password', 'replace-with-another-local-password', 'replace-with-a-random-secret', 'replace-with-a-jwt-passphrase')) {
        $content = $content.Replace($placeholder, (New-LocalSecret))
    }
    [IO.File]::WriteAllText($envPath, $content, [Text.UTF8Encoding]::new($false))
}
if ([IO.File]::ReadAllText($envPath).Contains('replace-with-')) { throw 'Compléter les secrets dans .env avant le démarrage.' }
$backendEnvPath = Join-Path $projectRoot 'backend\.env'
if (!(Test-Path -LiteralPath $backendEnvPath)) {
    Copy-Item -LiteralPath (Join-Path $projectRoot 'backend\.env.example') -Destination $backendEnvPath
}

$currentEnv = [IO.File]::ReadAllText($envPath)
foreach ($key in @('S3_ACCESS_KEY', 'S3_SECRET_KEY')) {
    if ($currentEnv -notmatch "(?m)^$key=") {
        $currentEnv += "`n$key=$(New-LocalSecret)`n"
    }
}
[IO.File]::WriteAllText($envPath, $currentEnv, [Text.UTF8Encoding]::new($false))

Invoke-Compose config --quiet
Invoke-Compose build php
Invoke-Compose up -d database pgadmin seaweedfs
Invoke-Compose run --rm php composer install --no-interaction
Invoke-Compose run --rm php php bin/console lexik:jwt:generate-keypair --skip-if-exists
Invoke-Compose run --rm php php bin/console doctrine:migrations:migrate --no-interaction
Invoke-Compose run --rm --no-deps frontend npm ci
Invoke-Compose up -d --wait
Invoke-Compose exec -T web nginx -s reload
Write-Host 'DataShare démarré. Consulter APP_PORT et PGADMIN_PORT dans .env pour les adresses locales.'
