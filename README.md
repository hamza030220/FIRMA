<p align="center">
  <img src="assets/image/firma.png" alt="FIRMA Logo" width="280">
</p>

<h3 align="center">🌿 Votre Ferme, Notre Technologie</h3>

<p align="center">
  <img src="https://img.shields.io/badge/Symfony-7.4-purple?logo=symfony" alt="Symfony 7.4">
  <img src="https://img.shields.io/badge/PHP-≥8.2-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/License-Proprietary-red" alt="License">
</p>

---

## 📋 Table des matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Base de données](#-base-de-données)
- [Routes & Endpoints](#-routes--endpoints)
- [Rôles & Sécurité](#-rôles--sécurité)
- [Stack technique](#-stack-technique)
- [Structure du projet](#-structure-du-projet)
- [Auteurs](#-auteurs)

---

## 🌱 À propos

**FIRMA** est une plateforme web agricole tout-en-un développée avec Symfony 7.4. Elle centralise la gestion d'une exploitation agricole moderne : marketplace d'équipements, gestion d'événements, réservation de techniciens et forum communautaire.

> **Marketplace, événements, techniciens, communauté — tout ce dont vous avez besoin, au même endroit.**

---

## ✨ Fonctionnalités

### 🛒 Marketplace
- Catalogue de produits agricoles (engrais, semences, matériel)
- Commandes en ligne avec paiement sécurisé (Stripe)
- Location d'équipements courte & longue durée
- Gestion fournisseurs & achats
- Gestion véhicules & terrains avec cartographie
- Validation des formulaires champ par champ avec notifications toast
- Recherche, filtres et pagination côté admin & utilisateur

### 📅 Événements
- Inscription aux foires, ateliers, formations et salons agricoles
- Ticket PDF avec QR code
- Gestion des accompagnants et participations

### 🔧 Techniciens
- Réservation de techniciens par géolocalisation
- Pré-diagnostic IA
- Avis et notations des interventions
- Suivi des demandes d'intervention

### 💬 Forum communautaire
- Discussions par catégorie thématique
- Posts et commentaires
- Communauté modérée

### 👥 Gestion des utilisateurs
- Authentification par email/mot de passe (bcrypt)
- 3 rôles : Client, Technicien, Administrateur
- Profils utilisateur avec informations détaillées
- Tableau de bord dédié par rôle

---

## 🏗 Architecture

```
                    ┌──────────────┐
                    │   Landing    │  (page publique)
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │    Login     │  (authentification)
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              ▼                         ▼
    ┌──────────────────┐     ┌──────────────────────┐
    │  Interface User  │     │  Interface Admin     │
    │   (top navbar)   │     │   (sidebar nav)      │
    ├──────────────────┤     ├──────────────────────┤
    │ • Marketplace    │     │ • Tableau de bord    │
    │ • Forum          │     │ • Gérer Marketplace  │
    │ • Techniciens    │     │ • Gérer Événements   │
    │ • Événements     │     │ • Gérer Techniciens  │
    │ • Profil         │     │ • Gérer Forum        │
    └──────────────────┘     │ • Gérer Utilisateurs │
                             └──────────────────────┘
```

---

## ⚙ Prérequis

| Outil | Version |
|-------|---------|
| PHP | ≥ 8.2 |
| Composer | ≥ 2.x |
| MariaDB / MySQL | ≥ 10.4 |
| XAMPP (recommandé) | ≥ 8.2 |
| Node.js (optionnel) | ≥ 18 |
| Git | ≥ 2.x |

---

## 🚀 Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/hamza030220/FIRMA.git
cd FIRMA
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les assets JavaScript

```bash
php bin/console importmap:install
```

### 4. Configurer l'environnement

Copier le fichier `.env` et adapter la configuration :

```bash
cp .env .env.local
```

Modifier `DATABASE_URL` dans `.env.local` selon votre configuration :

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/firma?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

### 5. Créer la base de données

```bash
# Option A : Importer le dump SQL (recommandé — inclut les données de test)
mysql -u root firma < firma.sql

# Option B : Via Doctrine (structure uniquement)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Lancer le serveur de développement

```bash
php -S 127.0.0.1:8000 -t public
```

Ouvrir [http://127.0.0.1:8000](http://127.0.0.1:8000) dans votre navigateur.

> **⚡ Conseil performance :** Si l'application est lente sur Windows/XAMPP, activez OPcache dans `php.ini` :
> ```ini
> zend_extension=opcache
> opcache.enable=1
> opcache.memory_consumption=256
> opcache.max_accelerated_files=20000
> ```

---

## 🗄 Base de données

Le schéma `firma` comporte **21 tables** :

| Table | Description |
|-------|-------------|
| `utilisateurs` | Comptes utilisateur (clients, admins, techniciens) |
| `profile` | Profils étendus des utilisateurs |
| `technicien` | Informations spécifiques aux techniciens |
| `categories` | Catégories de produits (équipement, véhicule, terrain) |
| `equipements` | Catalogue d'équipements agricoles |
| `vehicules` | Véhicules à louer (tracteurs, moissonneuses, camions) |
| `terrains` | Terrains à louer/vendre |
| `commandes` | Commandes clients |
| `details_commandes` | Lignes de commande |
| `fournisseurs` | Fournisseurs de produits |
| `achats_fournisseurs` | Achats en gros auprès des fournisseurs |
| `locations` | Locations de véhicules/terrains |
| `evenements` | Événements agricoles |
| `participations` | Inscriptions aux événements |
| `accompagnants` | Accompagnants des participants |
| `demande` | Demandes d'intervention technique |
| `avis` | Avis et notations des techniciens |
| `categorie_forum` | Catégories du forum |
| `post` | Publications du forum |
| `commentaire` | Commentaires sur les posts |
| `personne` | Table de référence de personnes |

---

## 🛤 Routes & Endpoints

### Pages publiques

| Méthode | URL | Nom | Description |
|---------|-----|-----|-------------|
| GET | `/` | `app_landing` | Page d'accueil |
| GET/POST | `/login` | `app_login` | Connexion |
| GET | `/logout` | `app_logout` | Déconnexion |

### Espace utilisateur (`/user`) — ROLE_USER

| Méthode | URL | Nom | Description |
|---------|-----|-----|-------------|
| GET | `/user` | `user_dashboard` | Tableau de bord |
| GET | `/user/marketplace` | `user_marketplace` | Marketplace |
| GET | `/user/forum` | `user_forum` | Forum |
| GET | `/user/techniciens` | `user_techniciens` | Techniciens |
| GET | `/user/evenements` | `user_evenements` | Événements |
| GET | `/user/profil` | `user_profile` | Profil |

### Espace administrateur (`/admin`) — ROLE_ADMIN

| Méthode | URL | Nom | Description |
|---------|-----|-----|-------------|
| GET | `/admin` | `admin_dashboard` | Tableau de bord |
| GET | `/admin/marketplace` | `admin_marketplace` | Gérer Marketplace |
| GET | `/admin/evenements` | `admin_evenements` | Gérer Événements |
| GET | `/admin/techniciens` | `admin_techniciens` | Gérer Techniciens |
| GET | `/admin/forum` | `admin_forum` | Gérer Forum |
| GET | `/admin/utilisateurs` | `admin_utilisateurs` | Gérer Utilisateurs |

---

## 🔒 Rôles & Sécurité

| Rôle | Type utilisateur | Accès |
|------|-----------------|-------|
| `ROLE_USER` | client | Espace utilisateur (`/user/*`) |
| `ROLE_TECHNICIEN` + `ROLE_USER` | technicien | Espace utilisateur |
| `ROLE_ADMIN` + `ROLE_USER` | admin | Espace admin (`/admin/*`) + utilisateur |

**Sécurité implémentée :**
- Authentification par formulaire avec protection CSRF
- Hachage automatique des mots de passe (bcrypt)
- Redirection après login basée sur le rôle (via `LoginSuccessHandler`)
- Contrôle d'accès par route (`#[IsGranted]`)

### Comptes de test

| Email | Type | Mot de passe |
|-------|------|-------------|
| `naama@firma.tn` | admin | *(voir base de données)* |
| `molkaajengui@gmail.com` | technicien | *(voir base de données)* |
| `hamza.slimani@esprit.tn` | client | *(voir base de données)* |

---

## 🧰 Stack technique

### Backend
| Technologie | Version |
|-------------|---------|
| PHP | ≥ 8.2 |
| Symfony | 7.4 |
| Doctrine ORM | 3.6 |
| Twig | 3.x |

### Frontend
| Technologie | Usage |
|-------------|-------|
| Symfony AssetMapper | Gestion des assets (pas de Webpack) |
| Stimulus (Hotwired) | Contrôleurs JS interactifs |
| Stripe.js | Paiement sécurisé en ligne |
| Leaflet / OpenStreetMap | Cartographie des terrains |
| Google Fonts | Playfair Display + Plus Jakarta Sans |
| CSS custom | Design système sur mesure |

### Base de données
| Technologie | Version |
|-------------|---------|
| MariaDB | 10.4.32 |
| XAMPP | Environnement local recommandé |

### Outils de développement
| Outil | Usage |
|-------|-------|
| PHPUnit 11.5 | Tests unitaires & fonctionnels |
| Symfony Profiler | Débogage & monitoring |
| Maker Bundle | Génération de code |

---

## 📁 Structure du projet

```
FIRMA/
├── assets/
│   ├── app.js                    # JS global (toast, confirm, alert, nav)
│   ├── marketplace.js            # JS Marketplace (tables, catalogue, panier, Stripe, maps)
│   ├── validation.js             # Validation champ par champ (toast popup)
│   ├── controllers.json          # Config Stimulus
│   ├── image/                    # Logos & images
│   ├── styles/
│   │   ├── app.css               # Styles globaux + toast + validation
│   │   ├── landing.css           # Page d'accueil
│   │   ├── login.css             # Page de connexion
│   │   ├── admin/dashboard.css   # Interface admin
│   │   └── user/dashboard.css    # Interface utilisateur
│   └── controllers/              # Contrôleurs Stimulus
├── config/
│   ├── packages/                 # Configuration des bundles
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   └── ...
│   ├── routes.yaml
│   └── services.yaml
├── migrations/                   # Migrations Doctrine
├── public/
│   └── index.php                 # Front controller
├── src/
│   ├── Controller/
│   │   ├── LandingController.php
│   │   ├── SecurityController.php
│   │   ├── Admin/DashboardController.php
│   │   └── User/DashboardController.php
│   ├── Entity/
│   │   └── User/Utilisateur.php
│   ├── Repository/
│   │   └── User/UtilisateurRepository.php
│   ├── Service/
│   │   └── User/LoginSuccessHandler.php
│   └── Kernel.php
├── templates/
│   ├── base.html.twig            # Layout racine
│   ├── landing.html.twig         # Page d'accueil
│   ├── security/login.html.twig  # Formulaire login
│   ├── admin/                    # Templates admin
│   │   ├── base.html.twig
│   │   ├── dashboard.html.twig
│   │   └── {module}/index.html.twig
│   └── user/                     # Templates utilisateur
│       ├── base.html.twig
│       ├── dashboard.html.twig
│       └── {module}/index.html.twig
├── tests/                        # Tests PHPUnit
├── firma.sql                     # Dump SQL complet
├── composer.json
└── .env
```

---

## 🧹 Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Lister toutes les routes
php bin/console debug:router

# Vérifier la config de sécurité
php bin/console debug:config security

# Lancer les tests
php bin/phpunit

# Créer une entité
php bin/console make:entity

# Générer une migration
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 👥 Auteurs

- **Hamza Slimani** — [hamza030220](https://github.com/hamza030220)

---

<p align="center">
  <strong>FIRMA</strong> — Votre Ferme, Notre Technologie 🌿
</p>
