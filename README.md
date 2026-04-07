# FIRMA

Plateforme web communautaire développée avec **Symfony 7.4** — gestion d'événements, forums, marketplace et ressources tech.

## Prérequis

- **PHP** >= 8.2
- **Composer** >= 2.x
- **MariaDB** 10.4+ (ou MySQL 8+)
- **XAMPP** (recommandé) ou serveur web équivalent
- **Symfony CLI** (optionnel, pour `symfony serve`)

## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo> firma
cd firma

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Modifier DATABASE_URL et MAILER_DSN dans .env.local

# 4. Créer la base de données et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Lancer le serveur
symfony serve
# ou
php -S localhost:8000 -t public/
```

## Configuration

Variables d'environnement principales (`.env.local`) :

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Connexion MariaDB (`mysql://root:@127.0.0.1:3306/firma`) |
| `MAILER_DSN` | Configuration SMTP pour l'envoi d'emails |
| `APP_SECRET` | Clé secrète Symfony |

## Architecture

```
src/
├── Controller/
│   ├── LandingController.php        # Page d'accueil publique
│   ├── SecurityController.php       # Authentification / Login
│   ├── Admin/
│   │   ├── DashboardController.php  # Tableau de bord admin
│   │   └── Event/                   # CRUD événements & sponsors (admin)
│   └── User/
│       ├── DashboardController.php  # Tableau de bord utilisateur
│       └── Event/                   # Participation aux événements
├── Entity/
│   ├── Event/                       # Evenement, Participation, Sponsor, Accompagnant
│   ├── User/                        # Utilisateur
│   ├── Forum/                       # (à venir)
│   ├── Marketplace/                 # (à venir)
│   └── Tech/                       # (à venir)
├── Form/
├── Repository/
└── Service/
```

## Modules

| Module | Statut | Description |
|--------|--------|-------------|
| **Event** | Actif | Gestion complète : événements, participations, sponsors, accompagnants |
| **User** | Actif | Authentification, profil, tableau de bord |
| **Forum** | Prévu | Discussions communautaires |
| **Marketplace** | Prévu | Place de marché |
| **Tech** | Prévu | Ressources techniques |

## Stack technique

- **Framework** : Symfony 7.4
- **ORM** : Doctrine ORM 3.6
- **Templates** : Twig 3
- **Base de données** : MariaDB 10.4
- **Assets** : Symfony AssetMapper + Stimulus + Turbo
- **PDF** : DomPDF 3.1
- **QR Codes** : endroid/qr-code 6.0
- **Emails** : Symfony Mailer (SMTP)
- **Validation** : Symfony Validator (Assert) + JS côté client

## Tests

```bash
# Lancer tous les tests
php bin/phpunit

# Lancer un test spécifique
php bin/phpunit tests/Unit/Event/
```

## Commandes utiles

```bash
# Vérifier le container Symfony
php bin/console lint:container

# Lister les routes
php bin/console debug:router

# Créer une migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear
```

## Licence

Projet académique — ESPRIT.
