<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109214040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Roles are the only user type: fill roles from type_of_user, then drop the column';
    }

    public function up(Schema $schema): void
    {
        // Keep everyone's effective permissions: admin = type 1 or ROLE_ADMIN in roles (both
        // counted before), vet = type 2. Other stored roles were not used by the app.
        $this->addSql(<<<'SQL'
            UPDATE user SET roles = CASE
                WHEN (type_of_user = 1 OR roles LIKE '%"ROLE_ADMIN"%') AND type_of_user = 2 THEN '["ROLE_ADMIN", "ROLE_VET"]'
                WHEN type_of_user = 1 OR roles LIKE '%"ROLE_ADMIN"%' THEN '["ROLE_ADMIN"]'
                WHEN type_of_user = 2 THEN '["ROLE_VET"]'
                ELSE '[]'
            END
            SQL);
        $this->addSql('ALTER TABLE user DROP type_of_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD type_of_user INT NOT NULL DEFAULT 3');
        $this->addSql(<<<'SQL'
            UPDATE user SET type_of_user = CASE
                WHEN roles LIKE '%"ROLE_ADMIN"%' THEN 1
                WHEN roles LIKE '%"ROLE_VET"%' THEN 2
                ELSE 3
            END
            SQL);
        $this->addSql('ALTER TABLE user ALTER type_of_user DROP DEFAULT');
    }
}
