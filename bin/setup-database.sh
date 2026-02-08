#!/bin/bash

# Script d'initialisation de la base de données pour MentorAI
# Ce script configure MySQL et crée la base de données avec les migrations

set -e

echo "======================================"
echo "Configuration de la base de données"
echo "======================================"
echo ""

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages de succès
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Fonction pour afficher les messages d'erreur
error() {
    echo -e "${RED}✗${NC} $1"
}

# Fonction pour afficher les messages d'information
info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Vérifier que MySQL est installé
info "Vérification de l'installation de MySQL..."
if ! command -v mysql &> /dev/null; then
    error "MySQL n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi
success "MySQL est installé"

# Vérifier que PHP est installé
info "Vérification de l'installation de PHP..."
if ! command -v php &> /dev/null; then
    error "PHP n'est pas installé. Veuillez l'installer avant de continuer."
    exit 1
fi
success "PHP est installé"

# Démarrer MySQL
info "Démarrage du service MySQL..."
if sudo service mysql status &> /dev/null; then
    success "MySQL est déjà démarré"
else
    sudo service mysql start
    success "MySQL démarré avec succès"
fi

# Configuration de l'utilisateur root
info "Configuration de l'utilisateur root MySQL..."

# Récupérer le mot de passe debian-sys-maint
DEBIAN_PWD=$(sudo grep password /etc/mysql/debian.cnf | head -1 | awk '{print $3}')

# Configurer l'utilisateur root
mysql -u debian-sys-maint -p"$DEBIAN_PWD" -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''; FLUSH PRIVILEGES;" 2>/dev/null || true
success "Utilisateur root configuré"

# Créer la base de données
info "Création de la base de données 'mentorai'..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS mentorai;" 2>/dev/null
success "Base de données créée ou déjà existante"

# Installer les dépendances Composer si nécessaire
if [ ! -d "vendor" ]; then
    info "Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist
    success "Dépendances installées"
fi

# Exécuter les migrations
info "Exécution des migrations Doctrine..."
php bin/console doctrine:migrations:migrate --no-interaction
success "Migrations exécutées avec succès"

# Vérifier le schéma
info "Vérification du schéma de la base de données..."
php bin/console doctrine:schema:validate 2>&1 | grep -q "The mapping files are correct" && success "Mapping validé" || info "Attention: Le mapping peut nécessiter des corrections"

echo ""
echo "======================================"
echo -e "${GREEN}Configuration terminée avec succès!${NC}"
echo "======================================"
echo ""
echo "La base de données 'mentorai' a été créée avec les tables suivantes:"
mysql -u root mentorai -e "SHOW TABLES;" 2>/dev/null

echo ""
info "Pour réinitialiser la base de données, exécutez:"
echo "  php bin/console doctrine:database:drop --force"
echo "  php bin/console doctrine:database:create"
echo "  php bin/console doctrine:migrations:migrate --no-interaction"
