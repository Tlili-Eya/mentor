<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204133840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_D2294458FB88E14F ON feedback (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458FB88E14F');
        $this->addSql('DROP INDEX IDX_D2294458FB88E14F ON feedback');
        $this->addSql('ALTER TABLE feedback DROP utilisateur_id');
    }
}
