# Tests

## API et compilation

```powershell
.\scripts\test.ps1
```

Le script applique les migrations à `datashare_test`, exécute les tests PHP, compile React et lance l'analyse du code front. Les tests PHP refusent de nettoyer une base dont le nom ne se termine pas par `_test`.

Vérification du 30 octobre 2026 : 13 tests PHP réussis, 45 assertions ; compilation front réussie ; analyse du code front sans erreur ni avertissement.

Les 13 tests fonctionnels vérifient : normalisation et unicité de l'e-mail ; hachage du mot de passe ; validation des champs ; JSON et types incorrects ; connexion ; accès au profil ; erreurs d'identifiants génériques ; JWT absent, modifié ou expiré ; limitation des tentatives ; absence de troncature des mots de passe longs.

## Parcours navigateur

Depuis `frontend`, avec Node 24 ou une version compatible avec Vite :

```powershell
npm ci
npx playwright install chromium
npm run test:e2e
```

Si Chrome est déjà installé, le téléchargement de Chromium peut être remplacé par :

```powershell
$env:PLAYWRIGHT_CHANNEL = 'chrome'
npm run test:e2e
```

L'application doit être démarrée. `E2E_BASE_URL` permet de changer l'adresse cible. Les tests créent deux comptes de parcours `parcours-…@example.test` dans la base de développement ; ils n'effacent pas les comptes existants. La limitation d'inscription peut imposer une attente avant plusieurs relances consécutives.

Trois parcours sont exécutés aux formats ordinateur et mobile : inscription/connexion/déconnexion ; refus d'identifiants incorrects ; confirmation du mot de passe et absence de débordement horizontal. Les captures et traces restent dans `frontend/test-results`, exclu du dépôt.

Vérification du 30 octobre 2026 : les 6 parcours ont réussi avec Chrome. Les captures des formulaires et de l'espace personnel ont été vérifiées.

## Limites actuelles

Le taux de couverture n'a pas encore été mesuré. Ces vérifications concernent l'authentification ; les tests des fichiers et des options seront ajoutés avec leur implémentation. L'émulation mobile ne remplace pas une vérification sur un appareil réel.
