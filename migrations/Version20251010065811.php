<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionXXXXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change required_level from array to single enum value';
    }

    public function up(Schema $schema): void
    {
        // Modifier la colonne pour être un simple VARCHAR
        $this->addSql('ALTER TABLE event MODIFY required_level VARCHAR(20) NOT NULL');
        
        // Si vous avez des données existantes, nettoyer les valeurs invalides
        $this->addSql("UPDATE event SET required_level = 'ALL_LEVELS' WHERE required_level NOT IN ('ALL_LEVELS', 'BEGINNER', 'INTERMEDIATE', 'ADVANCED')");
    }

    public function down(Schema $schema): void
    {
        // Retour arrière si besoin
        $this->addSql('ALTER TABLE event MODIFY required_level LONGTEXT NOT NULL');
    }
}