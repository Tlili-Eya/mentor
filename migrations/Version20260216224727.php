<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216224727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif ADD titre VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE parcours ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcours ADD CONSTRAINT FK_99B1DEE3FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_99B1DEE3FB88E14F ON parcours (utilisateur_id)');
        $this->addSql('ALTER TABLE programme ADD meilleure_medaille VARCHAR(255) DEFAULT NULL, ADD score_pourcentage INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_ai ADD contenu LONGTEXT NOT NULL, ADD created_at DATE NOT NULL, ADD updated_at DATE DEFAULT NULL, CHANGE article_id article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tache DROP score, CHANGE medaille titre VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif DROP titre, CHANGE description description VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE parcours DROP FOREIGN KEY FK_99B1DEE3FB88E14F');
        $this->addSql('DROP INDEX IDX_99B1DEE3FB88E14F ON parcours');
        $this->addSql('ALTER TABLE parcours DROP utilisateur_id');
        $this->addSql('ALTER TABLE programme DROP meilleure_medaille, DROP score_pourcentage');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE sortie_ai DROP contenu, DROP created_at, DROP updated_at, CHANGE article_id article_id INT NOT NULL');
        $this->addSql('ALTER TABLE tache ADD score INT NOT NULL, CHANGE titre medaille VARCHAR(255) NOT NULL');
    }
}
