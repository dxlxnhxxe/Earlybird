<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011181009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "clock" (id SERIAL NOT NULL, user_id INT NOT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BE7BBE92A76ED395 ON "clock" (user_id)');
        $this->addSql('ALTER TABLE "clock" ADD CONSTRAINT FK_BE7BBE92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX IDX_C4E0A61F783E3463');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F783E3463 ON team (manager_id)');
        $this->addSql('DROP INDEX user_email_key');
        $this->addSql('CREATE SEQUENCE user_id_seq');
        $this->addSql('SELECT setval(\'user_id_seq\', (SELECT MAX(id) FROM "user"))');
        $this->addSql('ALTER TABLE "user" ALTER id SET DEFAULT nextval(\'user_id_seq\')');
        $this->addSql('ALTER TABLE "user" ALTER firstname SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER phone_number SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER role SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER code_pin SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "clock" DROP CONSTRAINT FK_BE7BBE92A76ED395');
        $this->addSql('DROP TABLE "clock"');
        $this->addSql('ALTER TABLE "user" ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER firstname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER phone_number DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER role DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER code_pin DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_email_key ON "user" (email)');
        $this->addSql('DROP INDEX UNIQ_C4E0A61F783E3463');
        $this->addSql('CREATE INDEX IDX_C4E0A61F783E3463 ON "team" (manager_id)');
    }
}
