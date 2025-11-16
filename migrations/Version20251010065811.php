<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251010065811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change required_level from array (simple_array) to single enum-like string value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE event
            SET required_level = 'ALL_LEVELS'
            WHERE required_level IS NULL
               OR required_level = ''
               OR required_level NOT IN ('ALL_LEVELS', 'BEGINNER', 'INTERMEDIATE', 'ADVANCED')
        ");

        $this->addSql('ALTER TABLE event MODIFY required_level VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE event MODIFY required_level LONGTEXT NOT NULL COMMENT '(DC2Type:simple_array)'");
    }
}
