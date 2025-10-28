<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028093425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE documentation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE documentation (id INT NOT NULL, uuid UUID NOT NULL, bundle_name VARCHAR(255) NOT NULL, bundle_version VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, section_title VARCHAR(255) NOT NULL, repository_url VARCHAR(255) NOT NULL, content TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN documentation.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE image ALTER uuid TYPE UUID');
        $this->addSql('COMMENT ON COLUMN image.uuid IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE documentation_id_seq CASCADE');
        $this->addSql('DROP TABLE documentation');
        $this->addSql('ALTER TABLE image ALTER uuid TYPE UUID');
        $this->addSql('COMMENT ON COLUMN image.uuid IS NULL');
    }
}
