<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023115641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clock DROP start_time');
        $this->addSql('ALTER TABLE clock DROP end_time');
        $this->addSql('ALTER TABLE clock ALTER "timestamp" DROP DEFAULT');
        $this->addSql('ALTER TABLE clock ALTER type TYPE VARCHAR(20)');
        $this->addSql('DROP INDEX team_member_team_id_user_id_key');
        $this->addSql('ALTER TABLE team_member ALTER team_id SET NOT NULL');
        $this->addSql('ALTER TABLE team_member ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER firstname SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER phone_number SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER role SET NOT NULL');
        $this->addSql('ALTER INDEX user_email_key RENAME TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER firstname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER lastname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER phone_number DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER role DROP NOT NULL');
        $this->addSql('ALTER INDEX uniq_8d93d649e7927c74 RENAME TO user_email_key');
        $this->addSql('ALTER TABLE "clock" ADD start_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "clock" ADD end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "clock" ALTER timestamp SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE "clock" ALTER type TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE team_member ALTER team_id DROP NOT NULL');
        $this->addSql('ALTER TABLE team_member ALTER user_id DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX team_member_team_id_user_id_key ON team_member (team_id, user_id)');
    }
}
