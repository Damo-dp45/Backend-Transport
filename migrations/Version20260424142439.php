<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424142439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bagage (created_at DATETIME DEFAULT NULL, created_from_ip VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_from_ip VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, deleted_from_ip VARCHAR(255) DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, etatdelete TINYINT DEFAULT 0, id INT AUTO_INCREMENT NOT NULL, codebagage VARCHAR(255) NOT NULL, nomclient VARCHAR(255) NOT NULL, contactclient VARCHAR(255) NOT NULL, nature VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, poids INT NOT NULL, montant INT NOT NULL, montantforce TINYINT NOT NULL, statut VARCHAR(255) DEFAULT NULL, identreprise INT DEFAULT NULL, voyage_id INT NOT NULL, tarifbagage_id INT DEFAULT NULL, INDEX IDX_A82C571568C9E5AF (voyage_id), INDEX IDX_A82C5715938B927E (tarifbagage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tarifbagage (created_at DATETIME DEFAULT NULL, created_from_ip VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, updated_from_ip VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, deleted_from_ip VARCHAR(255) DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, etatdelete TINYINT DEFAULT 0, id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, poidsmin INT NOT NULL, poidsmax INT DEFAULT NULL, montant INT NOT NULL, identreprise INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bagage ADD CONSTRAINT FK_A82C571568C9E5AF FOREIGN KEY (voyage_id) REFERENCES voyage (id)');
        $this->addSql('ALTER TABLE bagage ADD CONSTRAINT FK_A82C5715938B927E FOREIGN KEY (tarifbagage_id) REFERENCES tarifbagage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bagage DROP FOREIGN KEY FK_A82C571568C9E5AF');
        $this->addSql('ALTER TABLE bagage DROP FOREIGN KEY FK_A82C5715938B927E');
        $this->addSql('DROP TABLE bagage');
        $this->addSql('DROP TABLE tarifbagage');
    }
}
