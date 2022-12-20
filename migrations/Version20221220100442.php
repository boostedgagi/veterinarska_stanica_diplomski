<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221220100442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714966F7FB6');
        $this->addSql('ALTER TABLE health_record CHANGE pet_id pet_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE pet CHANGE owner_id owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pet ADD CONSTRAINT FK_E4529B857E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64940369CAB');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64940369CAB FOREIGN KEY (vet_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B857E3C61F9');
        $this->addSql('ALTER TABLE pet CHANGE owner_id owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714966F7FB6');
        $this->addSql('ALTER TABLE health_record CHANGE pet_id pet_id INT NOT NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64940369CAB');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64940369CAB FOREIGN KEY (vet_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
