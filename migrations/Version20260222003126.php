<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222001325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute contrainte d\'unicité sur ordre par programme dans la table tache';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT unique_ordre_per_programme UNIQUE (programme_id, ordre)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tache DROP INDEX unique_ordre_per_programme');
    }
}