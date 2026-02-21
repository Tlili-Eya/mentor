<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220141521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE webauthn_credential (id INT AUTO_INCREMENT NOT NULL, credential_id VARCHAR(255) NOT NULL, public_key LONGTEXT NOT NULL, counter BIGINT NOT NULL, transports JSON NOT NULL, aaguid VARCHAR(36) DEFAULT NULL, attestation_type VARCHAR(255) DEFAULT NULL, user_handle VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_850123F92558A7A5 (credential_id), INDEX IDX_850123F9FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE webauthn_credential ADD CONSTRAINT FK_850123F9FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE webauthn_credential DROP FOREIGN KEY FK_850123F9FB88E14F');
        $this->addSql('DROP TABLE webauthn_credential');
    }
}
