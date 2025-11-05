<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table refresh_tokens nécessaire au bundle JWT Refresh Token
 */
final class Version20251023140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_tokens table for JWT refresh token functionality';
    }

    public function up(Schema $schema): void
    {
        // Create refresh_tokens table for JWT refresh token functionality
        $this->addSql('CREATE TABLE IF NOT EXISTS refresh_tokens (
            id SERIAL PRIMARY KEY,
            refresh_token VARCHAR(128) NOT NULL UNIQUE,
            username VARCHAR(255) NOT NULL,
            valid TIMESTAMP NOT NULL
        )');
        
        // Indexes for performance
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_refresh_token ON refresh_tokens(refresh_token)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_refresh_token_username ON refresh_tokens(username)');
    }

    public function down(Schema $schema): void
    {
        // Drop refresh_tokens table and its indexes
        $this->addSql('DROP INDEX IF EXISTS idx_refresh_token_username');
        $this->addSql('DROP INDEX IF EXISTS idx_refresh_token');
        $this->addSql('DROP TABLE IF EXISTS refresh_tokens');
    }
}