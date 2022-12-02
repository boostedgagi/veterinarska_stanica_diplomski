<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221202190105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE health_record (id INT AUTO_INCREMENT NOT NULL, vet_id INT NOT NULL, pet_id INT NOT NULL, examination_id INT NOT NULL, exam_date_time DATETIME NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, INDEX IDX_E0DE771440369CAB (vet_id), INDEX IDX_E0DE7714966F7FB6 (pet_id), INDEX IDX_E0DE7714DAD0CFBF (examination_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE771440369CAB FOREIGN KEY (vet_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id)');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714DAD0CFBF FOREIGN KEY (examination_id) REFERENCES examination (id)');
        $this->addSql('ALTER TABLE user ADD health_record_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497C7BDE9E FOREIGN KEY (health_record_id) REFERENCES health_record (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6497C7BDE9E ON user (health_record_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497C7BDE9E');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE771440369CAB');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714966F7FB6');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714DAD0CFBF');
        $this->addSql('DROP TABLE health_record');
        $this->addSql('DROP INDEX IDX_8D93D6497C7BDE9E ON user');
        $this->addSql('ALTER TABLE user DROP health_record_id');
    }
}
