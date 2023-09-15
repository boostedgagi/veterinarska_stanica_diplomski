<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230915124256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE on_call (id INT AUTO_INCREMENT NOT NULL, vet_id INT NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', chat_count INT NOT NULL, INDEX IDX_85E5256040369CAB (vet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE on_call ADD CONSTRAINT FK_85E5256040369CAB FOREIGN KEY (vet_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE health_record CHANGE made_by_vet made_by_vet TINYINT(1) NOT NULL, CHANGE notified notified_week_before TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE771440369CAB FOREIGN KEY (vet_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714DAD0CFBF FOREIGN KEY (examination_id) REFERENCES examination (id)');
        $this->addSql('ALTER TABLE pet ADD CONSTRAINT FK_E4529B857E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64940369CAB FOREIGN KEY (vet_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE on_call DROP FOREIGN KEY FK_85E5256040369CAB');
        $this->addSql('DROP TABLE on_call');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64940369CAB');
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B857E3C61F9');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE771440369CAB');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714966F7FB6');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714DAD0CFBF');
        $this->addSql('ALTER TABLE health_record CHANGE made_by_vet made_by_vet TINYINT(1) DEFAULT NULL, CHANGE notified_week_before notified TINYINT(1) NOT NULL');
    }
}
