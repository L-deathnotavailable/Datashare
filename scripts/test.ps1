$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))

function Invoke-Compose {
    & docker compose --project-directory $projectRoot -f (Join-Path $projectRoot 'compose.yaml') -p datashare @args
    if ($LASTEXITCODE -ne 0) { throw 'La vérification a échoué.' }
}

Invoke-Compose run --rm php php bin/console doctrine:database:create --env=test --if-not-exists
Invoke-Compose run --rm php php bin/console doctrine:migrations:migrate --env=test --no-interaction
Invoke-Compose run --rm php php bin/phpunit
Invoke-Compose run --rm --no-deps frontend npm run build
Invoke-Compose run --rm --no-deps frontend npm run lint
