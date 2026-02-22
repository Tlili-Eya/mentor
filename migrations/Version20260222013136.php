<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222013136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_8A8E26E9A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE sortie_ai_articles (sortie_ai_id INT NOT NULL, reference_article_id INT NOT NULL, INDEX IDX_917011B42450A84F (sortie_ai_id), INDEX IDX_917011B4268AB3D3 (reference_article_id), PRIMARY KEY (sortie_ai_id, reference_article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE sortie_ai_articles ADD CONSTRAINT FK_917011B42450A84F FOREIGN KEY (sortie_ai_id) REFERENCES sortie_ai (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sortie_ai_articles ADD CONSTRAINT FK_917011B4268AB3D3 FOREIGN KEY (reference_article_id) REFERENCES reference_article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_actions ADD etudiant_id INT DEFAULT NULL, ADD auteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE plan_actions ADD CONSTRAINT FK_BBF7B9E7DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE plan_actions ADD CONSTRAINT FK_BBF7B9E7DEFF315D FOREIGN KEY (feedback_auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE plan_actions ADD CONSTRAINT FK_BBF7B9E760BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_BBF7B9E7DDEAB1A3 ON plan_actions (etudiant_id)');
        $this->addSql('CREATE INDEX IDX_BBF7B9E7DEFF315D ON plan_actions (feedback_auteur_id)');
        $this->addSql('CREATE INDEX IDX_BBF7B9E760BB6FE6 ON plan_actions (auteur_id)');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_ai DROP FOREIGN KEY `FK_7A1A2FE67294869C`');
        $this->addSql('DROP INDEX IDX_7A1A2FE67294869C ON sortie_ai');
        $this->addSql('ALTER TABLE sortie_ai ADD statut VARCHAR(20) DEFAULT \'NOUVEAU\' NOT NULL, CHANGE article_id etudiant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_ai ADD CONSTRAINT FK_7A1A2FE6DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_7A1A2FE6DDEAB1A3 ON sortie_ai (etudiant_id)');
        $this->addSql('ALTER TABLE utilisateur ADD preferences JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE sortie_ai_articles DROP FOREIGN KEY FK_917011B42450A84F');
        $this->addSql('ALTER TABLE sortie_ai_articles DROP FOREIGN KEY FK_917011B4268AB3D3');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE sortie_ai_articles');
        $this->addSql('ALTER TABLE plan_actions DROP FOREIGN KEY FK_BBF7B9E7DDEAB1A3');
        $this->addSql('ALTER TABLE plan_actions DROP FOREIGN KEY FK_BBF7B9E7DEFF315D');
        $this->addSql('ALTER TABLE plan_actions DROP FOREIGN KEY FK_BBF7B9E760BB6FE6');
        $this->addSql('DROP INDEX IDX_BBF7B9E7DDEAB1A3 ON plan_actions');
        $this->addSql('DROP INDEX IDX_BBF7B9E7DEFF315D ON plan_actions');
        $this->addSql('DROP INDEX IDX_BBF7B9E760BB6FE6 ON plan_actions');
        $this->addSql('ALTER TABLE plan_actions DROP etudiant_id, DROP auteur_id');
        $this->addSql('ALTER TABLE ressource CHANGE url_ressource url_ressource VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE sortie_ai DROP FOREIGN KEY FK_7A1A2FE6DDEAB1A3');
        $this->addSql('DROP INDEX IDX_7A1A2FE6DDEAB1A3 ON sortie_ai');
        $this->addSql('ALTER TABLE sortie_ai DROP statut, CHANGE etudiant_id article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_ai ADD CONSTRAINT `FK_7A1A2FE67294869C` FOREIGN KEY (article_id) REFERENCES reference_article (id)');
        $this->addSql('CREATE INDEX IDX_7A1A2FE67294869C ON sortie_ai (article_id)');
        $this->addSql('ALTER TABLE utilisateur DROP preferences');
    }
}
