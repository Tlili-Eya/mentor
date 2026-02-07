<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206172907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE carnet (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, coleur VARCHAR(255) DEFAULT NULL, visibilite VARCHAR(255) DEFAULT NULL, date_creation DATETIME DEFAULT NULL, date_modification DATETIME DEFAULT NULL, utilisateurs_id INT DEFAULT NULL, INDEX IDX_576D26501E969C5 (utilisateurs_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE carnet_planning_etude (carnet_id INT NOT NULL, planning_etude_id INT NOT NULL, INDEX IDX_EA0890CBFA207516 (carnet_id), INDEX IDX_EA0890CB70AE85CF (planning_etude_id), PRIMARY KEY (carnet_id, planning_etude_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie_article (id INT AUTO_INCREMENT NOT NULL, nom_categorie VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATE NOT NULL, updated_at DATE DEFAULT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_5DB9A0C460BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, note INT NOT NULL, datefeedback DATE NOT NULL, typefeedback VARCHAR(255) NOT NULL, etatfeedback VARCHAR(255) NOT NULL, traitement_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D2294458DDA344B6 (traitement_id), INDEX IDX_D2294458FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE humeur (id INT AUTO_INCREMENT NOT NULL, valeur_humeur INT DEFAULT NULL, facteur_principal VARCHAR(255) DEFAULT NULL, moyenne7j INT DEFAULT NULL, moyenne30j INT DEFAULT NULL, tendance VARCHAR(255) DEFAULT NULL, niveau_risque VARCHAR(255) DEFAULT NULL, cree_le DATETIME DEFAULT NULL, profil_apprentissage_id INT DEFAULT NULL, INDEX IDX_F44F89D69664E32 (profil_apprentissage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE motivation (id INT AUTO_INCREMENT NOT NULL, dategeneratiomm DATE NOT NULL, messagemotivant VARCHAR(999) DEFAULT NULL, programme_id INT DEFAULT NULL, INDEX IDX_E06073ED62BB7AEE (programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE objectif (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(50) NOT NULL, datedebut DATE NOT NULL, datefin DATE NOT NULL, statut VARCHAR(255) NOT NULL, programme_id INT NOT NULL, utilisateur_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E2F8685162BB7AEE (programme_id), INDEX IDX_E2F86851FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE parcours (id INT AUTO_INCREMENT NOT NULL, type_parcours VARCHAR(255) NOT NULL, titre VARCHAR(255) NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, description LONGTEXT NOT NULL, etablissement VARCHAR(255) NOT NULL, diplome VARCHAR(255) NOT NULL, specialite VARCHAR(255) DEFAULT NULL, entreprise VARCHAR(255) DEFAULT NULL, poste VARCHAR(255) DEFAULT NULL, type_contrat VARCHAR(255) DEFAULT NULL, date_creation DATE NOT NULL, date_modification DATE DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan_actions (id INT AUTO_INCREMENT NOT NULL, decision VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATE NOT NULL, updated_at DATE DEFAULT NULL, statut VARCHAR(50) NOT NULL, sortie_ai_id INT DEFAULT NULL, INDEX IDX_BBF7B9E72450A84F (sortie_ai_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE planning_etude (id INT AUTO_INCREMENT NOT NULL, titre_p VARCHAR(255) DEFAULT NULL, date_seance DATE NOT NULL, heure_debut TIME DEFAULT NULL, heure_fin TIME DEFAULT NULL, matiere VARCHAR(255) DEFAULT NULL, type_activite VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, notes_pers LONGTEXT DEFAULT NULL, duree_prevue INT DEFAULT NULL, duree_reelle INT DEFAULT NULL, etat VARCHAR(255) DEFAULT NULL, date_creation DATETIME DEFAULT NULL, date_modification DATETIME DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_8C7213BBFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE profil_apprentissage (id INT AUTO_INCREMENT NOT NULL, niveau_concentration INT DEFAULT NULL, temps_moyen_apprentissage INT DEFAULT NULL, matieres_fortes VARCHAR(255) DEFAULT NULL, matieres_faibles VARCHAR(255) DEFAULT NULL, vitesse_apprentissage INT DEFAULT NULL, format_préféré VARCHAR(255) DEFAULT NULL, style_motivation VARCHAR(255) DEFAULT NULL, type_pers VARCHAR(255) DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_1D17FE05FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE programme (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, dategeneration DATE NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE projet (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, technologies VARCHAR(255) NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, date_creation DATE NOT NULL, date_modification DATE DEFAULT NULL, parcours_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_50159CA96E38C0DB (parcours_id), INDEX IDX_50159CA9FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reference_article (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, created_at DATE NOT NULL, updated_at DATE DEFAULT NULL, published TINYINT NOT NULL, categorie_id INT DEFAULT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_54AABCEBCF5E72D (categorie_id), INDEX IDX_54AABCE60BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ressource (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, url_ressource VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_creation DATE NOT NULL, date_modification DATE DEFAULT NULL, type_ressource VARCHAR(50) NOT NULL, projet_id INT DEFAULT NULL, INDEX IDX_939F4544C18272 (projet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE sortie_ai (id INT AUTO_INCREMENT NOT NULL, cible VARCHAR(20) NOT NULL, type_sortie VARCHAR(20) NOT NULL, criticite VARCHAR(20) NOT NULL, categorie_sortie VARCHAR(20) NOT NULL, article_id INT NOT NULL, INDEX IDX_7A1A2FE67294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE tache (id INT AUTO_INCREMENT NOT NULL, ordre INT NOT NULL, description VARCHAR(500) DEFAULT NULL, etat VARCHAR(255) NOT NULL, score INT NOT NULL, medaille VARCHAR(255) NOT NULL, programme_id INT DEFAULT NULL, INDEX IDX_9387207562BB7AEE (programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE traitement (id INT AUTO_INCREMENT NOT NULL, typetraitement VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, datetraitement DATE NOT NULL, decision VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mdp VARCHAR(255) NOT NULL, pdp_url VARCHAR(255) DEFAULT NULL, date_inscription DATE DEFAULT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE carnet ADD CONSTRAINT FK_576D26501E969C5 FOREIGN KEY (utilisateurs_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE carnet_planning_etude ADD CONSTRAINT FK_EA0890CBFA207516 FOREIGN KEY (carnet_id) REFERENCES carnet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE carnet_planning_etude ADD CONSTRAINT FK_EA0890CB70AE85CF FOREIGN KEY (planning_etude_id) REFERENCES planning_etude (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie_article ADD CONSTRAINT FK_5DB9A0C460BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458DDA344B6 FOREIGN KEY (traitement_id) REFERENCES traitement (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE humeur ADD CONSTRAINT FK_F44F89D69664E32 FOREIGN KEY (profil_apprentissage_id) REFERENCES profil_apprentissage (id)');
        $this->addSql('ALTER TABLE motivation ADD CONSTRAINT FK_E06073ED62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_E2F8685162BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_E2F86851FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE plan_actions ADD CONSTRAINT FK_BBF7B9E72450A84F FOREIGN KEY (sortie_ai_id) REFERENCES sortie_ai (id)');
        $this->addSql('ALTER TABLE planning_etude ADD CONSTRAINT FK_8C7213BBFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE profil_apprentissage ADD CONSTRAINT FK_1D17FE05FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA96E38C0DB FOREIGN KEY (parcours_id) REFERENCES parcours (id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA9FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reference_article ADD CONSTRAINT FK_54AABCEBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_article (id)');
        $this->addSql('ALTER TABLE reference_article ADD CONSTRAINT FK_54AABCE60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE ressource ADD CONSTRAINT FK_939F4544C18272 FOREIGN KEY (projet_id) REFERENCES projet (id)');
        $this->addSql('ALTER TABLE sortie_ai ADD CONSTRAINT FK_7A1A2FE67294869C FOREIGN KEY (article_id) REFERENCES reference_article (id)');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207562BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carnet DROP FOREIGN KEY FK_576D26501E969C5');
        $this->addSql('ALTER TABLE carnet_planning_etude DROP FOREIGN KEY FK_EA0890CBFA207516');
        $this->addSql('ALTER TABLE carnet_planning_etude DROP FOREIGN KEY FK_EA0890CB70AE85CF');
        $this->addSql('ALTER TABLE categorie_article DROP FOREIGN KEY FK_5DB9A0C460BB6FE6');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458DDA344B6');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458FB88E14F');
        $this->addSql('ALTER TABLE humeur DROP FOREIGN KEY FK_F44F89D69664E32');
        $this->addSql('ALTER TABLE motivation DROP FOREIGN KEY FK_E06073ED62BB7AEE');
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY FK_E2F8685162BB7AEE');
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY FK_E2F86851FB88E14F');
        $this->addSql('ALTER TABLE plan_actions DROP FOREIGN KEY FK_BBF7B9E72450A84F');
        $this->addSql('ALTER TABLE planning_etude DROP FOREIGN KEY FK_8C7213BBFB88E14F');
        $this->addSql('ALTER TABLE profil_apprentissage DROP FOREIGN KEY FK_1D17FE05FB88E14F');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA96E38C0DB');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA9FB88E14F');
        $this->addSql('ALTER TABLE reference_article DROP FOREIGN KEY FK_54AABCEBCF5E72D');
        $this->addSql('ALTER TABLE reference_article DROP FOREIGN KEY FK_54AABCE60BB6FE6');
        $this->addSql('ALTER TABLE ressource DROP FOREIGN KEY FK_939F4544C18272');
        $this->addSql('ALTER TABLE sortie_ai DROP FOREIGN KEY FK_7A1A2FE67294869C');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_9387207562BB7AEE');
        $this->addSql('DROP TABLE carnet');
        $this->addSql('DROP TABLE carnet_planning_etude');
        $this->addSql('DROP TABLE categorie_article');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE humeur');
        $this->addSql('DROP TABLE motivation');
        $this->addSql('DROP TABLE objectif');
        $this->addSql('DROP TABLE parcours');
        $this->addSql('DROP TABLE plan_actions');
        $this->addSql('DROP TABLE planning_etude');
        $this->addSql('DROP TABLE profil_apprentissage');
        $this->addSql('DROP TABLE programme');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE reference_article');
        $this->addSql('DROP TABLE ressource');
        $this->addSql('DROP TABLE sortie_ai');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE traitement');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
