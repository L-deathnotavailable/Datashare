<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030140633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des comptes utilisateurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, email VARCHAR(254) NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
    }
}
