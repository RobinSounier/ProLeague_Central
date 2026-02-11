# 🎮 PRO LEAGUE CENTRAL

Application web de gestion de tournois esport développée avec Symfony 7.

---

## 📋 Description

PRO LEAGUE CENTRAL est une plateforme permettant aux joueurs et organisateurs de créer, gérer et participer à des tournois esport. L'application offre une gestion complète des équipes, des jeux, des commentaires et un système de vote.

**Projet réalisé dans le cadre d'un projet scolaire.**

---

## 👥 Équipe de développement

| Membre | Rôle |
|--------|------|
| **Benjamin LACAZE** | Développeur |
| **Sofiane ROS** | Développeur |
| **Robin SOUNIER** | Développeur |

---

## ✨ Fonctionnalités

### Utilisateurs
- Inscription et authentification sécurisée
- Gestion de profil personnalisé
- Système de rôles (User / Admin)

### Tournois
- Création et gestion de tournois
- Définition des dates limites d'inscription
- Association à un jeu spécifique
- Upload de médias (images, documents)
- Inscription des équipes

### Équipes
- Création et gestion d'équipes
- Ajout/suppression de membres
- Inscription aux tournois
- Propriétaire d'équipe

### Jeux
- Catalogue des jeux disponibles
- Association aux tournois et équipes

### Interactions
- Système de commentaires
- Système de votes
- Notifications flash

### Administration
- Dashboard administrateur
- Gestion des utilisateurs
- Modération des commentaires
- Gestion des tournois, équipes et jeux

---

## 🛠️ Technologies

| Catégorie | Technologie |
|-----------|-------------|
| Framework | Symfony 8.0.5 |
| Langage | PHP 8.2+ |
| Base de données | MySQL / PostgreSQL |
| ORM | Doctrine |
| Frontend | Twig, Symfony |
| CSS | Tailwind CSS |
| Authentification | Symfony Security |
| Upload | Service FileUploader personnalisé |

---

## 📁 Structure du projet
```
src/
├── Controller/
│   ├── Admin/
│   │   ├── AdminController.php       # Dashboard admin
│   │   ├── CommentController.php     # CRUD commentaires (admin)
│   │   ├── GameController.php        # CRUD jeux (admin)
│   │   ├── TeamController.php        # CRUD équipes (admin)
│   │   ├── TournamentController.php  # CRUD tournois (admin)
│   │   └── UserController.php        # CRUD utilisateurs (admin)
│   ├── CommentController.php         # Gestion des commentaires
│   ├── HomeController.php            # Page d'accueil
│   ├── ProfilController.php          # Gestion du profil utilisateur
│   ├── RegistrationController.php    # Inscription
│   ├── SecurityController.php        # Login / Logout
│   ├── TeamController.php            # Gestion des équipes
│   ├── TournamentController.php      # Gestion des tournois
│   └── VoteController.php            # Système de votes
│
├── DataFixtures/
│   └── AppFixtures.php               # Données de test
│
├── Entity/
│   ├── Comment.php                   # Entité commentaire
│   ├── Game.php                      # Entité jeu
│   ├── Media.php                     # Entité média (fichiers uploadés)
│   ├── Team.php                      # Entité équipe
│   ├── Tournament.php                # Entité tournoi
│   ├── User.php                      # Entité utilisateur
│   └── Vote.php                      # Entité vote
│
├── Form/
│   ├── Admin/
│   │   └── TeamType.php              # Formulaire équipe (admin)
│   ├── CommentType.php               # Formulaire commentaire
│   ├── GameType.php                  # Formulaire jeu
│   ├── ProfilType.php                # Formulaire profil
│   ├── RegistrationFormType.php      # Formulaire inscription
│   ├── TeamType.php                  # Formulaire équipe
│   └── TournamentType.php            # Formulaire tournoi
│
├── Repository/
│   ├── CommentRepository.php
│   ├── GameRepository.php
│   ├── MediaRepository.php
│   ├── TeamRepository.php
│   ├── TournamentRepository.php
│   ├── UserRepository.php
│   └── VoteRepository.php
│
├── Service/
│   └── FileUploader.php              # Service d'upload de fichiers
│
└── Kernel.php
```

---

## ⚙️ Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL ou PostgreSQL
- Node.js et npm
- Symfony CLI (optionnel)

### Étapes d'installation

**1. Cloner le repository**
```bash
git clone https://github.com/votre-username/esport-tournament.git
cd esport-tournament
```

**2. Installer les dépendances PHP**
```bash
composer install
```

**3. Configurer l'environnement**
```bash
cp .env .env.local
```

Modifier `.env.local` avec vos paramètres :
```env
APP_ENV=dev
APP_SECRET=votre_secret_key
DATABASE_URL="mysql://username:password@127.0.0.1:3306/esport_tournament?charset=utf8mb4"
```

**4. Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**5. Charger les fixtures**
```bash
php bin/console doctrine:fixtures:load
```

**6. Installer les assets frontend**
```bash
npm install
npm run build
```

**7. Lancer le serveur**
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

Application accessible sur : `http://127.0.0.1:8000`

---

## 👤 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@admin.com | admin |
| User | user1@euser.com | user |
| User | user2@user.com | user |

---

## 🔐 Rôles et permissions

| Fonctionnalité | Visiteur | ROLE_USER | ROLE_ADMIN |
|----------------|----------|-----------|------------|
| Voir les tournois | ✅ | ✅ | ✅ |
| Voir les équipes | ✅ | ✅ | ✅ |
| S'inscrire | ✅ | ❌ | ❌ |
| Se connecter | ✅ | ✅ | ✅ |
| Créer un tournoi | ❌ | ✅ | ✅ |
| Modifier ses tournois | ❌ | ✅ | ✅ |
| Créer une équipe | ❌ | ✅ | ✅ |
| Rejoindre une équipe | ❌ | ✅ | ✅ |
| Commenter | ❌ | ✅ | ✅ |
| Voter | ❌ | ✅ | ✅ |
| Modifier son profil | ❌ | ✅ | ✅ |
| Accès panel admin | ❌ | ❌ | ✅ |
| Gérer tous les users | ❌ | ❌ | ✅ |
| Gérer tous les tournois | ❌ | ❌ | ✅ |
| Gérer toutes les équipes | ❌ | ❌ | ✅ |
| Gérer les jeux | ❌ | ❌ | ✅ |
| Modérer commentaires | ❌ | ❌ | ✅ |

---

## 🗄️ Modèle de données

### User
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| email | string | Email (unique) |
| password | string | Mot de passe hashé |
| username | string | Pseudo |
| roles | array | Rôles (ROLE_USER, ROLE_ADMIN) |
| createdAt | datetime | Date de création |

### Tournament
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| name | string | Nom du tournoi |
| description | text | Description |
| deadline | datetime | Date de fin |
| deadlineJoin | datetime | Date limite d'inscription |
| isActive | boolean | Statut actif |
| owner | User | Créateur du tournoi |
| game | Game | Jeu associé |
| teams | Collection | Équipes inscrites |
| medias | Collection | Fichiers uploadés |
| createdAt | datetime | Date de création |

### Team
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| name | string | Nom de l'équipe |
| description | text | Description |
| isActive | boolean | Statut actif |
| owner | User | Propriétaire |
| game | Game | Jeu principal |
| users | Collection | Membres |
| tournaments | Collection | Tournois inscrits |
| createdAt | datetime | Date de création |

### Game
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| name | string | Nom du jeu |
| description | text | Description |
| image | string | Image du jeu |

### Comment
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| content | text | Contenu |
| author | User | Auteur |
| tournament | Tournament | Tournoi associé |
| createdAt | datetime | Date de création |

### Vote
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| user | User | Votant |
| tournament | Tournament | Tournoi voté |
| value | int | Valeur du vote |

### Media
| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant unique |
| path | string | Chemin du fichier |
| tournament | Tournament | Tournoi associé |

---

## 📝 Commandes utiles
```bash
# Cache
php bin/console cache:clear

# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:database:drop --force
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --purge-with-truncate

# Génération
php bin/console make:entity
php bin/console make:controller
php bin/console make:form
php bin/console make:migration

# Debug
php bin/console debug:router
php bin/console debug:container

# Assets
npm run watch
npm run build
```

---

## 🐛 Résolution de problèmes

### Erreur d'upload de fichiers
Vérifier les permissions du dossier `public/uploads/` :
```bash
chmod -R 755 public/uploads/
```

### Erreur de connexion BDD
Vérifier le fichier `.env.local` et tester la connexion :
```bash
php bin/console doctrine:database:create
```

---

## 📄 Licence

Projet scolaire - Tous droits réservés.

---

## 👨‍💻 Auteurs

Projet réalisé par :
- **Benjamin LACAZE**
- **Sofiane ROS**
- **Robin SOUNIER**

Développé avec ❤️ pour la communauté esport.
