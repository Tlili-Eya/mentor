<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209020341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sortie_ai (id INT AUTO_INCREMENT NOT NULL, cible VARCHAR(20) NOT NULL, type_sortie VARCHAR(20) NOT NULL, criticite VARCHAR(20) NOT NULL, categorie_sortie VARCHAR(20) NOT NULL, contenu LONGTEXT NOT NULL, created_at DATE NOT NULL, updated_at DATE DEFAULT NULL, article_id INT DEFAULT NULL, INDEX IDX_7A1A2FE67294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE sortie_ai ADD CONSTRAINT FK_7A1A2FE67294869C FOREIGN KEY (article_id) REFERENCES reference_article (id)');
        $this->addSql('ALTER TABLE objectif ADD titre VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE plan_actions CHANGE date date DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE feedback_date feedback_date DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP reset_token, DROP reset_token_expires_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_ai DROP FOREIGN KEY FK_7A1A2FE67294869C');
        $this->addSql('DROP TABLE sortie_ai');
        $this->addSql('ALTER TABLE objectif DROP titre, CHANGE description description VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE plan_actions CHANGE date date DATE NOT NULL, CHANGE updated_at updated_at DATE DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE feedback_date feedback_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }
}
