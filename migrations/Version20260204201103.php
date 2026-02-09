<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204201103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_ai_reference_article DROP FOREIGN KEY `FK_15D5AD5D2450A84F`');
        $this->addSql('ALTER TABLE sortie_ai_reference_article DROP FOREIGN KEY `FK_15D5AD5D268AB3D3`');
        $this->addSql('DROP TABLE sortie_ai_reference_article');
        $this->addSql('ALTER TABLE sortie_ai ADD created_at DATE NOT NULL, ADD updated_at DATE DEFAULT NULL, ADD article_id INT DEFAULT NULL, CHANGE contenu contenu LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE sortie_ai ADD CONSTRAINT FK_7A1A2FE67294869C FOREIGN KEY (article_id) REFERENCES reference_article (id)');
        $this->addSql('CREATE INDEX IDX_7A1A2FE67294869C ON sortie_ai (article_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sortie_ai_reference_article (sortie_ai_id INT NOT NULL, reference_article_id INT NOT NULL, INDEX IDX_15D5AD5D2450A84F (sortie_ai_id), INDEX IDX_15D5AD5D268AB3D3 (reference_article_id), PRIMARY KEY (sortie_ai_id, reference_article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sortie_ai_reference_article ADD CONSTRAINT `FK_15D5AD5D2450A84F` FOREIGN KEY (sortie_ai_id) REFERENCES sortie_ai (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie_ai_reference_article ADD CONSTRAINT `FK_15D5AD5D268AB3D3` FOREIGN KEY (reference_article_id) REFERENCES reference_article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie_ai DROP FOREIGN KEY FK_7A1A2FE67294869C');
        $this->addSql('DROP INDEX IDX_7A1A2FE67294869C ON sortie_ai');
        $this->addSql('ALTER TABLE sortie_ai DROP created_at, DROP updated_at, DROP article_id, CHANGE contenu contenu TEXT DEFAULT NULL');
    }
}
