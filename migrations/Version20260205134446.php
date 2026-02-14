<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205134446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_actions ADD feedback_enseignant LONGTEXT DEFAULT NULL, ADD feedback_date DATE DEFAULT NULL, ADD feedback_auteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE plan_actions ADD CONSTRAINT FK_BBF7B9E7DEFF315D FOREIGN KEY (feedback_auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_BBF7B9E7DEFF315D ON plan_actions (feedback_auteur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_actions DROP FOREIGN KEY FK_BBF7B9E7DEFF315D');
        $this->addSql('DROP INDEX IDX_BBF7B9E7DEFF315D ON plan_actions');
        $this->addSql('ALTER TABLE plan_actions DROP feedback_enseignant, DROP feedback_date, DROP feedback_auteur_id');
    }
}
