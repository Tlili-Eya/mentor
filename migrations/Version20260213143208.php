<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213143208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plan_actions_articles (plan_actions_id INT NOT NULL, reference_article_id INT NOT NULL, INDEX IDX_D727B247DC3271E0 (plan_actions_id), INDEX IDX_D727B247268AB3D3 (reference_article_id), PRIMARY KEY (plan_actions_id, reference_article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE plan_actions_articles ADD CONSTRAINT FK_D727B247DC3271E0 FOREIGN KEY (plan_actions_id) REFERENCES plan_actions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_actions_articles ADD CONSTRAINT FK_D727B247268AB3D3 FOREIGN KEY (reference_article_id) REFERENCES reference_article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plan_actions_articles DROP FOREIGN KEY FK_D727B247DC3271E0');
        $this->addSql('ALTER TABLE plan_actions_articles DROP FOREIGN KEY FK_D727B247268AB3D3');
        $this->addSql('DROP TABLE plan_actions_articles');
    }
}
