<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stored_file (
            id UUID PRIMARY KEY,
            owner_id UUID NOT NULL REFERENCES app_user (id),
            original_name VARCHAR(255) NOT NULL,
            size_bytes BIGINT NOT NULL CHECK (size_bytes BETWEEN 1 AND 1000000000),
            mime_type VARCHAR(255) NOT NULL,
            storage_key VARCHAR(255) NOT NULL UNIQUE,
            share_token VARCHAR(43) NOT NULL UNIQUE,
            created_at TIMESTAMPTZ NOT NULL,
            expires_at TIMESTAMPTZ NOT NULL
        )');
        $this->addSql('CREATE INDEX stored_file_owner_idx ON stored_file (owner_id)');
        $this->addSql('CREATE INDEX stored_file_expiry_idx ON stored_file (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stored_file');
    }
}
