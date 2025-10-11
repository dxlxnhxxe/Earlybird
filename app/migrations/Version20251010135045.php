<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010135045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE team_member (team_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES "team" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT team_user_team_id_fkey');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT team_user_user_id_fkey');
        $this->addSql('DROP TABLE team_user');
        $this->addSql('DROP INDEX IDX_C4E0A61F783E3463');
        $this->addSql('ALTER TABLE team DROP members');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F783E3463 ON team (manager_id)');
        $this->addSql('DROP INDEX user_email_key');
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
        $this->addSql('CREATE TABLE team_user (team_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_5C722232296CD8AE ON team_user (team_id)');
        $this->addSql('CREATE INDEX IDX_5C722232A76ED395 ON team_user (user_id)');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT team_user_team_id_fkey FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT team_user_user_id_fkey FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1A76ED395');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('DROP INDEX UNIQ_C4E0A61F783E3463');
        $this->addSql('ALTER TABLE "team" ADD members JSON DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_C4E0A61F783E3463 ON "team" (manager_id)');
        $this->addSql('ALTER TABLE "user" ALTER firstname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER phone_number DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER role DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER code_pin DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_email_key ON "user" (email)');
    }
}
