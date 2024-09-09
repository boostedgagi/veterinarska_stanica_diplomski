<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240907085512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714DAD0CFBF');
        $this->addSql('ALTER TABLE health_record CHANGE examination_id examination_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714DAD0CFBF FOREIGN KEY (examination_id) REFERENCES examination (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714DAD0CFBF');
        $this->addSql('ALTER TABLE health_record CHANGE examination_id examination_id INT NOT NULL');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714DAD0CFBF FOREIGN KEY (examination_id) REFERENCES examination (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
