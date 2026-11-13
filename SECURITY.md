# Sécurité

## Contrôles actuels

- Mots de passe de 8 à 4096 caractères, hachés avec Argon2id via Sodium et un sel généré par le hasher.
- E-mails normalisés et unicité garantie par PostgreSQL, y compris en cas d'inscriptions concurrentes.
- JWT signés, validés côté serveur et expirant après 3600 secondes.
- Profil protégé côté serveur, aucune donnée de mot de passe dans les réponses.
- JWT conservé en mémoire du navigateur ; réponses API avec `Cache-Control: no-store`.
- Limitation des connexions : 5 échecs par identifiant/IP en une minute et limite globale par IP gérée par Symfony.
- Limitation des inscriptions : 5 requêtes par IP en 15 minutes.
- Secrets locaux dans `.env` ; clés PEM hors du dépôt ; exemples sans secret utilisable.
- Base de données non publiée sur Windows ; application et pgAdmin limités à 127.0.0.1.

Le JWT utilise l'en-tête Authorization, sans cookie d'authentification de compte. La déconnexion du front n'invalide pas un jeton copié avant sa suppression locale. Aucun renouvellement ou mécanisme de révocation n'est encore implémenté.

## Vérification des dépendances

```powershell
docker compose -p datashare exec php composer audit
docker compose -p datashare exec frontend npm audit
```

Vérification du 30 octobre 2026 : aucune vulnérabilité connue signalée par ces deux outils. Ce résultat dépend de leurs bases d'avis et ne constitue pas un audit complet du code ou des images Docker.

## Avant une mise en production

Préparer une configuration dédiée : HTTPS, front compilé, debug désactivé, droits d'accès aux fichiers, sauvegardes, journalisation et surveillance. Les limites applicatives de tentatives ne remplacent pas une protection contre un déni de service au niveau réseau. Le projet Compose actuel reste un environnement local de développement.
