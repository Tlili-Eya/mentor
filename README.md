# MentorAI

Application de mentorat et d'accompagnement pédagogique basée sur Symfony 6.4.

## Prérequis

- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/Tlili-Eya/mentor.git
cd mentor
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

Copier le fichier `.env` et ajuster les variables si nécessaire :

```bash
cp .env .env.local
```

### 4. Configuration de la base de données

#### Option 1 : Script automatique (recommandé)

```bash
./bin/setup-database.sh
```

Ce script effectue automatiquement :
- Démarrage de MySQL
- Configuration de l'utilisateur root
- Création de la base de données
- Exécution des migrations

#### Option 2 : Configuration manuelle

Voir la documentation détaillée dans [docs/DATABASE_SETUP.md](docs/DATABASE_SETUP.md)

```bash
# Démarrer MySQL
sudo service mysql start

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Lancer l'application

```bash
symfony server:start
```

ou

```bash
php -S localhost:8000 -t public
```

## Structure de la base de données

La base de données `mentorai` contient 21 tables principales :

- **utilisateur** - Gestion des utilisateurs
- **parcours** - Parcours d'apprentissage
- **projet** - Projets des utilisateurs
- **objectif** - Objectifs pédagogiques
- **feedback** - Retours et évaluations
- **planning_etude** - Planification des études
- **carnet** - Carnets de notes
- **motivation** - Suivi de la motivation
- **humeur** - Suivi de l'humeur
- **programme** - Programmes d'études
- **profil_apprentissage** - Profils d'apprentissage
- **plan_actions** - Plans d'action
- **ressource** - Ressources pédagogiques
- **tache** - Tâches à accomplir
- **categorie_article** - Catégories d'articles
- **reference_article** - Articles de référence
- **traitement** - Traitements
- **sortie_ai** - Sorties de l'IA
- et plus...

## Documentation

- [Configuration de la base de données](docs/DATABASE_SETUP.md)

## Technologies utilisées

- **Framework** : Symfony 6.4
- **ORM** : Doctrine
- **Base de données** : MySQL 8.0
- **PHP** : 8.1+

## Commandes utiles

### Base de données

```bash
# Vérifier le statut des migrations
php bin/console doctrine:migrations:status

# Valider le schéma de la base de données
php bin/console doctrine:schema:validate

# Réinitialiser la base de données
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### Cache

```bash
# Vider le cache
php bin/console cache:clear
```

## Licence

Propriétaire
