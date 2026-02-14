<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213220219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_actions ADD created_at DATE NOT NULL, DROP date, DROP categorie, DROP feedback_enseignant, DROP feedback_date, DROP feedback_auteur_id, CHANGE updated_at updated_at DATE DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_ai DROP contenu, DROP created_at, DROP updated_at, CHANGE article_id article_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_actions ADD date DATETIME NOT NULL, ADD categorie VARCHAR(255) DEFAULT NULL, ADD feedback_enseignant LONGTEXT DEFAULT NULL, ADD feedback_date DATETIME DEFAULT NULL, ADD feedback_auteur_id INT DEFAULT NULL, DROP created_at, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE sortie_ai ADD contenu LONGTEXT NOT NULL, ADD created_at DATE NOT NULL, ADD updated_at DATE DEFAULT NULL, CHANGE article_id article_id INT DEFAULT NULL');
    }
}
