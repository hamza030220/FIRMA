<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les seuils meteo temp_min temp_max humidite_min a la table maladie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maladie ADD temp_min DOUBLE PRECISION DEFAULT NULL, ADD temp_max DOUBLE PRECISION DEFAULT NULL, ADD humidite_min INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maladie DROP temp_min, DROP temp_max, DROP humidite_min');
    }
}
