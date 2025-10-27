<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251024123119 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image ADD uuid UUID NULL');
        $this->addSql('UPDATE image SET uuid = gen_random_uuid()');
        $this->addSql('ALTER TABLE image ALTER COLUMN uuid SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image DROP uuid');
    }
}
