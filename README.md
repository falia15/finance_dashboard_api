# API Backend

API REST/JSON-LD basée sur [Symfony](https://symfony.com) 7.4 (LTS) et [API Platform](https://api-platform.com), packagée avec Docker.

## Stack

- **Symfony 7.4** (LTS, support jusqu'à nov. 2029)
- **API Platform 5** (`api-platform/api-pack`) — expose automatiquement les entités Doctrine en API REST/JSON-LD, avec doc Swagger intégrée
- **Doctrine ORM + Migrations**
- **PostgreSQL 16**
- **FrankenPHP** (PHP 8.5) + **Caddy** — serveur web avec HTTPS auto-signé, HTTP/2 et HTTP/3
- **NelmioCorsBundle** — CORS déjà ouvert sur `localhost` (utile pour un frontend développé séparément)
- **symfony/maker-bundle** (dev) — génération d'entités/CRUD

## Prérequis

- [Docker](https://www.docker.com/) + Docker Compose v2

Aucune installation locale de PHP/Composer n'est nécessaire : tout tourne dans les conteneurs.

## Configuration

Après un `git clone`, créer un `.env.dev.local` (non versionné, propre à ta machine) avec un `APP_SECRET` :

```bash
echo "APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')" > .env.dev.local
```

## Démarrage

```bash
docker compose build      # première fois, ou après modif du Dockerfile
docker compose up -d      # démarre les conteneurs (php + database)
docker compose down       # arrête les conteneurs
docker compose logs -f php   # suivre les logs
```

- API browsable (Swagger UI) : **https://localhost/api**
- Endpoints JSON-LD : **https://localhost/api/{resource}** (ex. `https://localhost/api/books`)

Le certificat TLS est auto-signé en local, un avertissement navigateur est normal.

## Développement

Créer une nouvelle ressource API (entité + exposition API Platform) :

```bash
docker compose exec php bin/console make:entity --api-resource
```

Générer et appliquer une migration après modification d'une entité :

```bash
docker compose exec php bin/console make:migration
docker compose exec php bin/console doctrine:migrations:migrate
```

Lancer une commande Symfony quelconque :

```bash
docker compose exec php bin/console <commande>
```

## Point d'attention

Le conteneur de développement tourne en `root`. Les fichiers qu'il génère (migrations, entités via `make:*`, `vendor/`, etc.) peuvent apparaître appartenant à `root` sur l'hôte. Si besoin de les rendre éditables :

```bash
docker run --rm -v "$(pwd)":/app -w /app alpine:3 chown -R $(id -u):$(id -g) .
```

## Ressources

- [Documentation API Platform](https://api-platform.com/docs/)
- [Documentation Symfony](https://symfony.com/doc)
- [Template Docker utilisé (dunglas/symfony-docker)](https://github.com/dunglas/symfony-docker) — voir le dossier [`docs/`](docs/) pour les options avancées (MySQL, Xdebug, production, etc.)
