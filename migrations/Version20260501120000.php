<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Performance: indexes on evenements (statut, date_debut, type_evenement),
 * composite booking-check index on locations, and latitude/longitude
 * columns for event geolocation.
 */
final class Version20260501120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add latitude/longitude to evenements + performance indexes (Event & Marketplace).';
    }

    public function up(Schema $schema): void
    {
        // evenements: lat / lng for map markers
        $this->addSql("ALTER TABLE evenements ADD latitude NUMERIC(10, 7) DEFAULT NULL, ADD longitude NUMERIC(10, 7) DEFAULT NULL");

        // evenements: indexes for filtered list queries
        $this->addSql("CREATE INDEX idx_evt_statut ON evenements (statut)");
        $this->addSql("CREATE INDEX idx_evt_date_debut ON evenements (date_debut)");
        $this->addSql("CREATE INDEX idx_evt_type ON evenements (type_evenement)");

        // locations: composite index for booking-overlap checks
        $this->addSql("CREATE INDEX idx_loc_booking_check ON locations (type_location, date_debut, date_fin, statut)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX idx_loc_booking_check ON locations");
        $this->addSql("DROP INDEX idx_evt_type ON evenements");
        $this->addSql("DROP INDEX idx_evt_date_debut ON evenements");
        $this->addSql("DROP INDEX idx_evt_statut ON evenements");
        $this->addSql("ALTER TABLE evenements DROP COLUMN latitude, DROP COLUMN longitude");
    }
}
