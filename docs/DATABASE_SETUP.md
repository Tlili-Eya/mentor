# Configuration de la Base de Données

Ce document décrit la procédure pour créer et configurer la base de données du projet MentorAI.

## Prérequis

- MySQL 8.0 ou supérieur
- PHP 8.1 ou supérieur
- Composer
- Symfony CLI (optionnel, pour lancer le serveur de développement)

## Configuration

### 1. Démarrage du serveur MySQL

```bash
sudo service mysql start
```

### 2. Configuration de l'utilisateur root

Si l'utilisateur root nécessite une configuration :

```bash
# Se connecter avec l'utilisateur debian-sys-maint
mysql -u debian-sys-maint -p$(sudo grep password /etc/mysql/debian.cnf | head -1 | awk '{print $3}')

# Dans la console MySQL, exécuter :
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Création de la base de données

```bash
# Via Doctrine (recommandé)
php bin/console doctrine:database:create --if-not-exists

# Ou directement via MySQL
mysql -u root -e "CREATE DATABASE IF NOT EXISTS mentorai;"
```

### 4. Exécution des migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Cette commande crée toutes les tables nécessaires :
- utilisateur
- projet
- parcours
- planning_etude
- carnet
- objectif
- feedback
- motivation
- humeur
- programme
- profil_apprentissage
- plan_actions
- ressource
- tache
- categorie_article
- reference_article
- traitement
- sortie_ai
- messenger_messages
- doctrine_migration_versions

### 5. Vérification du schéma

```bash
# Vérifier que le schéma est valide
php bin/console doctrine:schema:validate

# Afficher la liste des tables
php bin/console doctrine:schema:update --dump-sql
```

## Variables d'environnement

La configuration de la base de données se trouve dans le fichier `.env` :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/mentorai"
```

Pour l'environnement de test, utilisez `.env.test` avec une base de données séparée.

## Dépannage

### Erreur de connexion

Si vous rencontrez une erreur `Connection refused` :
1. Vérifiez que MySQL est démarré : `sudo service mysql status`
2. Redémarrez le service : `sudo service mysql restart`

### Erreur d'authentification

Si vous avez une erreur `Access denied` :
1. Vérifiez les credentials dans `.env`
2. Reconfigurez l'utilisateur root comme décrit dans la section "Configuration de l'utilisateur root"

### Réinitialisation de la base de données

```bash
# Supprimer la base de données
php bin/console doctrine:database:drop --force

# Recréer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

## Structure de la base de données

La base de données utilise Doctrine ORM avec les entités suivantes :

- **Utilisateur** : Gestion des utilisateurs de la plateforme
- **Projet** : Projets des utilisateurs
- **Parcours** : Parcours d'apprentissage
- **Objectif** : Objectifs définis par les utilisateurs
- **Feedback** : Retours et évaluations
- **PlanningEtude** : Planning d'études
- **Carnet** : Carnets de notes
- Et plus...

Pour plus de détails sur les entités, consultez le répertoire `src/Entity/`.
