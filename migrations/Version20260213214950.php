<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213214950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif DROP titre, CHANGE description description VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE programme DROP meilleure_medaille, DROP score_pourcentage');
        $this->addSql('ALTER TABLE tache ADD score INT NOT NULL, CHANGE titre medaille VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL, ADD status VARCHAR(20) DEFAULT \'actif\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif ADD titre VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE programme ADD meilleure_medaille VARCHAR(255) DEFAULT NULL, ADD score_pourcentage INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE tache DROP score, CHANGE medaille titre VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP reset_token, DROP reset_token_expires_at, DROP status');
    }
}
