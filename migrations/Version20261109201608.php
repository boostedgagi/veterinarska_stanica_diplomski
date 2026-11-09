<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109201608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind verification / password-reset tokens to the user they were issued for';
    }

    public function up(Schema $schema): void
    {
        // Existing tokens keep user_id NULL and are rejected; DeleteExpiredTokensCommand removes them.
        $this->addSql('ALTER TABLE token ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5F37A13BA76ED395 ON token (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13BA76ED395');
        $this->addSql('DROP INDEX IDX_5F37A13BA76ED395 ON token');
        $this->addSql('ALTER TABLE token DROP user_id');
    }
}
