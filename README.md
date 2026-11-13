# DataShare

Application de partage de fichiers.

## Stack

React et TypeScript avec Vite ; API Symfony 7.4 et PHP 8.4 ; Doctrine et PostgreSQL 17 ; Nginx ; pgAdmin 4 pour l'administration locale. Les dépendances sont verrouillées dans `composer.lock` et `package-lock.json`.

## Installation locale

Prérequis : Docker Desktop démarré en mode conteneurs Linux et PowerShell. Vérifier que les ports 8088 et 5058 sont disponibles ; les changer dans `.env` si nécessaire. Le nom Compose réservé à ce projet est `datashare`.

Depuis la racine du projet :

```powershell
.\scripts\setup.ps1
```

Le script crée les secrets locaux si `.env` est absent, construit PHP, installe les dépendances, génère les clés JWT si elles sont absentes, applique les migrations et démarre les services. Il ne remplace pas les secrets existants et ne supprime aucun volume.

- Application : http://localhost:8088
- API de disponibilité : http://localhost:8088/api/health
- pgAdmin : http://localhost:5058

La configuration Compose est destinée au développement local. Les serveurs Vite et le mode debug Symfony ne constituent pas un déploiement de production.

## Compte pgAdmin

Se connecter avec `PGADMIN_DEFAULT_EMAIL` et `PGADMIN_DEFAULT_PASSWORD` du fichier `.env`. Dans le groupe DataShare, ouvrir le serveur « DataShare local » et saisir `POSTGRES_PASSWORD`.

Le serveur est préconfiguré avec l'hôte `database`, le port `5432`, la base `datashare` et l'utilisateur `datashare`. En cas de modification de ces deux derniers paramètres dans `.env`, adapter aussi `docker/pgadmin/servers.json`. PostgreSQL n'a pas de port publié sur Windows. Le compte pgAdmin est indépendant des comptes de l'application.

## Commandes courantes

```powershell
docker compose -p datashare up -d
docker compose -p datashare ps
docker compose -p datashare logs --tail 50 web php frontend
docker compose -p datashare stop
```

Toutes les commandes sont limitées au projet `datashare`. Les volumes conservent les bases, les paramètres pgAdmin et les dépendances après un arrêt. Modifier le mot de passe PostgreSQL dans `.env` après sa première initialisation ne change pas automatiquement celui stocké dans la base.

## Authentification

Créer un compte sur `/inscription`, puis se connecter sur `/connexion`. Le mot de passe comporte au moins 8 caractères et est haché avec Argon2id. Le JWT expire après une heure. Il reste en mémoire du navigateur : actualiser ou fermer la page nécessite une nouvelle connexion. La déconnexion efface la session locale ; un JWT précédemment copié reste valide jusqu'à son expiration.

Les fonctions de dépôt et de téléchargement ne sont pas encore implémentées.

## Organisation

```text
backend/     API, entités, migrations et tests PHP
frontend/    Interface React et tests navigateur
docker/      Configuration PHP, Nginx et pgAdmin
scripts/     Installation et vérifications
doc/         Architecture et contrat d'interface
compose.yaml
```

Consulter [l'architecture](doc/architecture.md), [le contrat d'authentification](backend/config/openapi/auth.yaml), [les tests](TESTING.md) et [la sécurité](SECURITY.md).


## Documentation interactive

Swagger UI : http://localhost:8088/api/docs
Contrat JSON : http://localhost:8088/api/docs.jsonopenapi

API Platform est intégré à Symfony. Les contrôleurs d'authentification existants sont documentés par une fabrique OpenAPI utilisant backend/config/openapi/auth.yaml. Aucune collection publique d'utilisateurs n'est exposée.

Dans Swagger : ouvrir POST /api/auth/login, cliquer Try it out, saisir email et password, puis Execute. Copier accessToken, cliquer Authorize et coller le jeton seul. GET /api/me permet ensuite de vérifier son profil. Les opérations exécutées modifient réellement la base locale. L'autorisation n'est pas conservée après rechargement.
