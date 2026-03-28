<div align="center">

<br/>

```
OPENSHARE
```

Plateforme Collaborative de Partage de Ressources Open Source

<br/>

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Apache](https://img.shields.io/badge/Apache-2.4+-D22128?style=flat-square&logo=apache&logoColor=white)](https://apache.org)
[![License](https://img.shields.io/badge/License-MIT-22C55E?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-En%20développement-F59E0B?style=flat-square)]()
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-6366F1?style=flat-square)](CONTRIBUTING.md)



</div>

---

## Table des matières

- [À propos du projet](#-à-propos-du-projet)
- [Fonctionnalités](#-fonctionnalités)
- [Technologies utilisées](#-technologies-utilisées)
- [Architecture du projet](#-architecture-du-projet)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Structure du projet](#-structure-du-projet)
- [Base de données](#-base-de-données)
- [Utilisation](#-utilisation)
- [Tests](#-tests)
- [Déploiement](#-déploiement)
- [Contribuer](#-contribuer)
- [Licence](#-licence)

---

## À propos du projet

**OpenShare** est une plateforme web collaborative permettant aux développeurs, étudiants et chercheurs de **partager, découvrir et télécharger des ressources open source** (code source, documentation, scripts, templates, projets académiques).

Le projet répond à un besoin réel dans l'écosystème académique francophone : centraliser les ressources techniques dans un espace modéré, structuré et accessible à tous.

```
Visiteur ──▶ Recherche & consultation
Utilisateur ──▶ Publication · Téléchargement · Notation · Commentaires
Administrateur ──▶ Validation · Modération · Statistiques · Gestion

## Fonctionnalités

### Espace Utilisateur
- ✅ Inscription / Connexion sécurisée (bcrypt + sessions PHP)
- ✅ Gestion du profil personnel
- ✅ **Publication de ressources** en 3 étapes (infos → upload → confirmation)
- ✅ **Recherche full-text** avec filtres (catégorie, licence, note, date)
- ✅ **Téléchargement sécurisé** avec historique
- ✅ **Système de notation** 1 à 5 étoiles (une note par ressource)
- ✅ **Commentaires** et discussions sur les ressources
- ✅ **Signalement** de contenu inapproprié

### Espace Administrateur
- ✅ Tableau de bord analytique (KPIs, graphiques, statistiques)
- ✅ **Validation / Rejet** des ressources soumises
- ✅ Gestion complète des utilisateurs (suspension, suppression)
- ✅ Traitement des signalements
- ✅ Consultation des statistiques de téléchargement

### Technique
- ✅ Architecture **MVC** en PHP natif
- ✅ Base de données **MySQL** avec 10 tables, triggers automatiques et vues SQL
- ✅ Interface **responsive** HTML/CSS/JavaScript
- ✅ Protection **CSRF, XSS, injection SQL** (PDO + requêtes préparées)
- ✅ Upload de fichiers sécurisé (validation MIME + taille)
- ✅ Moteur de recherche **FULLTEXT** MySQL

---

## Technologies utilisées

| Couche | Technologie | Version |
|--------|-------------|---------|
| **Backend** | PHP | 8.1+ |
| **Base de données** | MySQL | 8.0+ |
| **Serveur web** | Apache | 2.4+ |
| **Frontend** | HTML5 / CSS3 / JavaScript | ES6+ |
| **Architecture** | MVC (Modèle-Vue-Contrôleur) | — |
| **Environnement local** | XAMPP / WAMP | — |
| **Versioning** | Git + GitHub | — |
| **Tests** | PHPUnit (unitaires) | 10+ |

---

## Architecture du projet

```
┌─────────────────────────────────────────────────────────┐
│                    COUCHE CLIENTE                        │
│          HTML5 · CSS3 · JavaScript (ES6+)                │
└──────────────────────┬──────────────────────────────────┘
                       │  HTTP / HTTPS
┌──────────────────────▼──────────────────────────────────┐
│                  SERVEUR WEB (Apache)                    │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │ Contrôleurs │  │  Modèles PHP │  │  Vues HTML/PHP│  │
│  │    PHP      │  │  (Entités)   │  │  (Templates)  │  │
│  └─────────────┘  └──────────────┘  └───────────────┘  │
│                  Architecture MVC                        │
└──────────────────────┬──────────────────────────────────┘
                       │  PDO (SQL préparé)
┌──────────────────────▼──────────────────────────────────┐
│                  BASE DE DONNÉES (MySQL)                 │
│  utilisateurs · ressources · fichiers · commentaires    │
│  notes · catégories · signalements · téléchargements    │
└─────────────────────────────────────────────────────────┘
```

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé :

- **PHP** `>= 8.1` avec les extensions : `pdo_mysql`, `fileinfo`, `mbstring`
- **MySQL** `>= 8.0`
- **Apache** `>= 2.4` avec `mod_rewrite` activé
- **Composer** (optionnel, pour les dépendances PHP)
- **Git**

> **Recommandé pour le développement local :** [XAMPP](https://www.apachefriends.org/) ou [WAMP](https://www.wampserver.com/)

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-username/openShare.git
cd openShare
```

### 2. Configurer Apache

Placez le projet dans le répertoire web de votre serveur :

```bash
# XAMPP (Windows)
C:\xampp\htdocs\openShare\

# WAMP (Windows)
C:\wamp64\www\openShare\

# Linux / macOS
/var/www/html/openShare/
```

### 3. Créer la base de données

Importez le script SQL fourni :

```bash
# Via ligne de commande
mysql -u root -p < sql/openShare.sql

# Ou via phpMyAdmin :
# Onglet "Importer" → Sélectionner sql/openShare.sql → Exécuter
```

### 4. Configurer la connexion

Copiez le fichier de configuration exemple :

```bash
cp includes/config.example.php includes/config.php
```

Éditez `includes/config.php` avec vos paramètres :

```php
<?php
define('DB_HOST',     'localhost');
define('DB_NAME',     'openShare_db');
define('DB_USER',     'root');
define('DB_PASS',     '');          // Votre mot de passe MySQL
define('DB_CHARSET',  'utf8mb4');

define('BASE_URL',    'http://localhost/openShare');
define('UPLOAD_DIR',  __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 52428800);  // 50 MB en octets
define('SECRET_KEY',  'changez-cette-cle-secrete-en-production');
```

### 5. Configurer les permissions

```bash
# Linux / macOS
chmod 755 assets/uploads/
chmod 644 includes/config.php
```

### 6. Lancer l'application

Démarrez Apache et MySQL (XAMPP/WAMP), puis ouvrez :

```
http://localhost/openShare
```

---

## Configuration

### Comptes de test disponibles

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Administrateur** | `admin@openShare.com` | `Password123!` |
| **Utilisateur** | `ahmed@mail.com` | `Password123!` |
| **Utilisateur** | `sara@mail.com` | `Password123!` |

> **Important :** Changez tous les mots de passe avant tout déploiement en production.

### Variables d'environnement

| Variable | Description | Défaut |
|----------|-------------|--------|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_NAME` | Nom de la base | `openShare_db` |
| `MAX_FILE_SIZE` | Taille max upload | `52428800` (50 MB) |
| `SECRET_KEY` | Clé secrète CSRF | À définir |

---

## Structure du projet

```
openShare/
│
├── 📄 index.php                 ← Page d'accueil + recherche
├── 📄 login.php                 ← Connexion
├── 📄 register.php              ← Inscription
├── 📄 publish.php               ← Publication de ressource
├── 📄 detail.php                ← Détail + téléchargement
├── 📄 logout.php                ← Déconnexion
│
├── 📁 admin/                    ← Espace administrateur
│   ├── dashboard.php            ← Tableau de bord
│   ├── resources.php            ← Gestion des ressources
│   ├── users.php                ← Gestion des utilisateurs
│   └── reports.php              ← Traitement des signalements
│
├── 📁 includes/                 ← Composants réutilisables
│   ├── config.php               ← Configuration (ignoré par Git)
│   ├── config.example.php       ← Modèle de configuration
│   ├── db.php                   ← Connexion PDO MySQL
│   ├── auth.php                 ← Authentification + sessions
│   ├── functions.php            ← Fonctions utilitaires
│   ├── header.php               ← En-tête HTML (navbar)
│   └── footer.php               ← Pied de page HTML
│
├── 📁 assets/
│   ├── css/
│   │   └── style.css            ← Feuille de styles principale
│   ├── js/
│   │   └── main.js              ← Scripts JavaScript
│   └── uploads/                 ← Fichiers uploadés (ignoré par Git)
│
├── 📁 sql/
│   ├── openShare.sql            ← Script complet (tables + données)
│   └── migrations/              ← Scripts de mise à jour
│
├── 📁 tests/                    ← Tests PHPUnit
│   ├── AuthTest.php
│   ├── ResourceTest.php
│   └── DatabaseTest.php
│
├── 📄 .htaccess                 ← Configuration Apache (URL rewriting)
├── 📄 .gitignore
├── 📄 composer.json
└── 📄 README.md
```

---

## Base de données

Le schéma comprend **10 tables** interconnectées :

```sql
utilisateurs        -- Membres et administrateurs
ressources          -- Ressources publiées (métadonnées)
fichiers            -- Fichiers attachés aux ressources
categories          -- Classification (Java, Python, PHP...)
commentaires        -- Commentaires sur les ressources
notes               -- Notations 1-5 étoiles
signalements        -- Contenus signalés
telechargements     -- Historique des téléchargements
sessions            -- Gestion sécurisée des sessions
```

### Triggers automatiques

```sql
after_note_insert        -- Recalcule note_moyenne automatiquement
after_note_update        -- Met à jour note_moyenne après modification
after_telechargement_insert -- Incrémente nb_telechargements
```

### Vues SQL utiles

```sql
vue_ressources_publiques  -- Jointure complète pour l'affichage public
vue_stats_admin           -- Agrégats pour le tableau de bord admin
vue_top_ressources        -- Top 10 les plus téléchargées
```

---

## Utilisation

### Publier une ressource

1. Créer un compte ou se connecter
2. Cliquer sur **"Publier une ressource"**
3. Remplir le formulaire en 3 étapes :
   - **Étape 1** — Titre, description, catégorie, licence, tags
   - **Étape 2** — Upload du fichier (ZIP, PDF, TAR.GZ · max 50 MB)
   - **Étape 3** — Récapitulatif et confirmation
4. La ressource passe en statut **"En attente"** jusqu'à validation admin

### Rechercher une ressource

```
# Recherche par mot-clé
http://localhost/openShare/?q=java+gestion

# Filtre par catégorie
http://localhost/openShare/?categorie=Python

# Filtre combiné
http://localhost/openShare/?q=flask&licence=MIT&note=4
```

### Accéder au dashboard admin

```
http://localhost/openShare/admin/dashboard.php
```

> Connexion requise avec un compte ayant le rôle `ADMIN`.


## Tests

### Lancer les tests unitaires (PHPUnit)

```bash
# Installer PHPUnit via Composer
composer require --dev phpunit/phpunit

# Lancer tous les tests
./vendor/bin/phpunit tests/

# Lancer un fichier de test spécifique
./vendor/bin/phpunit tests/AuthTest.php

# Générer un rapport de couverture
./vendor/bin/phpunit --coverage-html docs/coverage tests/
```

### Résultats attendus

```
PHPUnit 10.x — Plateforme OpenShare
..............................                                   30 / 30 (100%)

Tests:  30  ·  Assertions: 84  ·  Failures: 0  ·  Errors: 0
```

---

## Déploiement

### Prérequis serveur de production

- PHP 8.1+ avec extensions `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`
- MySQL 8.0+
- Apache 2.4+ avec `mod_rewrite` et `mod_headers`
- Certificat SSL (Let's Encrypt recommandé)


## 🤝 Contribuer

Les contributions sont les bienvenues ! Voici comment participer :

```bash
# 1. Forker le projet
# 2. Créer une branche pour votre fonctionnalité
git checkout -b feature/nouvelle-fonctionnalite

# 3. Committer vos changements
git commit -m "feat: ajout de la fonctionnalité X"

# 4. Pusher la branche
git push origin feature/nouvelle-fonctionnalite

# 5. Ouvrir une Pull Request
```

**Encadrant :** DR. MOUBARAK BARREH HASSAN— Département Informatique

---

## 📄 Licence

Ce projet est distribué sous licence **MIT**. Voir le fichier [`LICENSE`](LICENSE) pour plus de détails.

```
MIT License — Copyright (c) 2026 Ahmed Benali & Sara Idrissi

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software [...] subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 🙏 Remerciements

- [PHP Documentation](https://www.php.net/docs.php) — Référence officielle PHP
- [MySQL Reference Manual](https://dev.mysql.com/doc/) — Documentation MySQL
- [OWASP Top 10](https://owasp.org/www-project-top-ten/) — Bonnes pratiques de sécurité web
- [Shields.io](https://shields.io/) — Badges README
- La communauté **open source** mondiale pour l'inspiration

---

<div align="center">

**⚡ OpenShare** — Partagez le savoir, librement.

*Fait avec passion dans le cadre d'un PFE · 2026*

[![GitHub stars](https://img.shields.io/github/stars/votre-username/openShare?style=social)](../../stargazers)
[![GitHub forks](https://img.shields.io/github/forks/votre-username/openShare?style=social)](../../network/members)

</div>
