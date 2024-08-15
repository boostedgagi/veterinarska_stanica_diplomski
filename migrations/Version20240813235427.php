<?php
/*
//
//declare(strict_types=1);
//
//namespace DoctrineMigrations;
//
//use Doctrine\DBAL\Schema\Schema;
//use Doctrine\Migrations\AbstractMigration;
//
///**
// * Auto-generated Migration: Please modify to your needs!
// */
//final class Version20240813235427 extends AbstractMigration
//{
//    public function getDescription(): string
//    {
//        return '';
//    }
//
//    public function up(Schema $schema): void
//    {
//        // this up() migration is auto-generated, please modify it to your needs
//        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', chat_id VARCHAR(255) DEFAULT NULL, INDEX IDX_2C9211FEF624B39D (sender_id), INDEX IDX_2C9211FECD53EDB6 (receiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE examination (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, duration INT NOT NULL, price INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE health_record (id INT AUTO_INCREMENT NOT NULL, vet_id INT DEFAULT NULL, pet_id INT DEFAULT NULL, examination_id INT NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, notified_week_before TINYINT(1) NOT NULL, made_by_vet TINYINT(1) NOT NULL, notified_day_before TINYINT(1) NOT NULL, INDEX IDX_E0DE771440369CAB (vet_id), INDEX IDX_E0DE7714966F7FB6 (pet_id), INDEX IDX_E0DE7714DAD0CFBF (examination_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE log (id INT AUTO_INCREMENT NOT NULL, device VARCHAR(64) NOT NULL, country VARCHAR(128) NOT NULL, ip_address VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE on_call (id INT AUTO_INCREMENT NOT NULL, vet_id INT NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_85E5256040369CAB (vet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE pet (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, date_of_birth DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', animal VARCHAR(255) NOT NULL, breed VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_E4529B857E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, expires VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, vet_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, allowed TINYINT(1) NOT NULL, type_of_user INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, phone VARCHAR(32) DEFAULT NULL, latitude VARCHAR(255) DEFAULT NULL, longitude VARCHAR(255) DEFAULT NULL, popularity DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64940369CAB (vet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
//        $this->addSql('ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FEF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
//        $this->addSql('ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FECD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id)');
//        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE771440369CAB FOREIGN KEY (vet_id) REFERENCES user (id) ON DELETE SET NULL');
//        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE SET NULL');
//        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE7714DAD0CFBF FOREIGN KEY (examination_id) REFERENCES examination (id)');
//        $this->addSql('ALTER TABLE on_call ADD CONSTRAINT FK_85E5256040369CAB FOREIGN KEY (vet_id) REFERENCES user (id)');
//        $this->addSql('ALTER TABLE pet ADD CONSTRAINT FK_E4529B857E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
//        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64940369CAB FOREIGN KEY (vet_id) REFERENCES user (id) ON DELETE SET NULL');
//    }
//
//    public function down(Schema $schema): void
//    {
//        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE contact_message DROP FOREIGN KEY FK_2C9211FEF624B39D');
//        $this->addSql('ALTER TABLE contact_message DROP FOREIGN KEY FK_2C9211FECD53EDB6');
//        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE771440369CAB');
//        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714966F7FB6');
//        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE7714DAD0CFBF');
//        $this->addSql('ALTER TABLE on_call DROP FOREIGN KEY FK_85E5256040369CAB');
//        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B857E3C61F9');
//        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64940369CAB');
//        $this->addSql('DROP TABLE contact_message');
//        $this->addSql('DROP TABLE examination');
//        $this->addSql('DROP TABLE health_record');
//        $this->addSql('DROP TABLE log');
//        $this->addSql('DROP TABLE on_call');
//        $this->addSql('DROP TABLE pet');
//        $this->addSql('DROP TABLE token');
//        $this->addSql('DROP TABLE user');
//        $this->addSql('DROP TABLE messenger_messages');
//    }
//}
//
