<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421085623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE courrier (created_at DATETIME DEFAULT NULL, created_from_ip VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_from_ip VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, deleted_from_ip VARCHAR(255) DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, etatdelete TINYINT DEFAULT 0, id INT AUTO_INCREMENT NOT NULL, codecourrier VARCHAR(255) NOT NULL, nomexpediteur VARCHAR(255) NOT NULL, contactexpediteur VARCHAR(255) NOT NULL, nomdestinataire VARCHAR(255) NOT NULL, contactdestinataire VARCHAR(255) NOT NULL, fraissuivi INT DEFAULT NULL, montant INT NOT NULL, statut VARCHAR(50) NOT NULL, identreprise INT DEFAULT NULL, garedepart_id INT NOT NULL, garearrivee_id INT NOT NULL, voyage_id INT DEFAULT NULL, INDEX IDX_BEF47CAA16887400 (garedepart_id), INDEX IDX_BEF47CAAB466CD0 (garearrivee_id), INDEX IDX_BEF47CAA68C9E5AF (voyage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE detailcourrier (id INT AUTO_INCREMENT NOT NULL, nature VARCHAR(255) NOT NULL, designation VARCHAR(255) DEFAULT NULL, emballage VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, poids INT DEFAULT NULL, valeur INT NOT NULL, montant INT NOT NULL, courrier_id INT NOT NULL, tarifcourrier_id INT DEFAULT NULL, INDEX IDX_AD731D028BF41DC7 (courrier_id), INDEX IDX_AD731D02B952E85D (tarifcourrier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tarifcourrier (created_at DATETIME DEFAULT NULL, created_from_ip VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_from_ip VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, deleted_from_ip VARCHAR(255) DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, etatdelete TINYINT DEFAULT 0, id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, valeurmin INT NOT NULL, valeurmax INT DEFAULT NULL, montanttaxe INT NOT NULL, identreprise INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE courrier ADD CONSTRAINT FK_BEF47CAA16887400 FOREIGN KEY (garedepart_id) REFERENCES gare (id)');
        $this->addSql('ALTER TABLE courrier ADD CONSTRAINT FK_BEF47CAAB466CD0 FOREIGN KEY (garearrivee_id) REFERENCES gare (id)');
        $this->addSql('ALTER TABLE courrier ADD CONSTRAINT FK_BEF47CAA68C9E5AF FOREIGN KEY (voyage_id) REFERENCES voyage (id)');
        $this->addSql('ALTER TABLE detailcourrier ADD CONSTRAINT FK_AD731D028BF41DC7 FOREIGN KEY (courrier_id) REFERENCES courrier (id)');
        $this->addSql('ALTER TABLE detailcourrier ADD CONSTRAINT FK_AD731D02B952E85D FOREIGN KEY (tarifcourrier_id) REFERENCES tarifcourrier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE courrier DROP FOREIGN KEY FK_BEF47CAA16887400');
        $this->addSql('ALTER TABLE courrier DROP FOREIGN KEY FK_BEF47CAAB466CD0');
        $this->addSql('ALTER TABLE courrier DROP FOREIGN KEY FK_BEF47CAA68C9E5AF');
        $this->addSql('ALTER TABLE detailcourrier DROP FOREIGN KEY FK_AD731D028BF41DC7');
        $this->addSql('ALTER TABLE detailcourrier DROP FOREIGN KEY FK_AD731D02B952E85D');
        $this->addSql('DROP TABLE courrier');
        $this->addSql('DROP TABLE detailcourrier');
        $this->addSql('DROP TABLE tarifcourrier');
    }
}
