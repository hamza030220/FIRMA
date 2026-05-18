<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table sponsors + données initiales (10 sponsors tunisiens)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS sponsors (
                id_sponsor INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(200) NOT NULL,
                logo_url VARCHAR(255),
                site_web VARCHAR(255),
                email_contact VARCHAR(150),
                telephone VARCHAR(20),
                description TEXT,
                montant_contribution DECIMAL(10,2) DEFAULT 0,
                secteur_activite ENUM('tech','finance','sante','education','industrie','autre') DEFAULT 'autre',
                id_evenement INT,
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_evenement) REFERENCES evenements(id_evenement) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ");

        $this->addSql("
            INSERT INTO sponsors (nom, logo_url, site_web, email_contact, telephone, description, secteur_activite) VALUES
            ('Tunisie Telecom', NULL, 'https://www.tunisietelecom.tn', 'contact@tunisietelecom.tn', '+216 71 123 456', 'Opérateur de télécommunications national tunisien', 'tech'),
            ('Ooredoo Tunisie',  NULL, 'https://www.ooredoo.tn',        'contact@ooredoo.tn',        '+216 22 123 456', 'Opérateur de télécommunications international', 'tech'),
            ('BIAT',             NULL, 'https://www.biat.com.tn',       'contact@biat.com.tn',       '+216 71 340 733', 'Banque Internationale Arabe de Tunisie', 'finance'),
            ('Banque de Tunisie',NULL, 'https://www.bt.com.tn',         'contact@bt.com.tn',         '+216 71 332 000', 'Banque de Tunisie - services bancaires', 'finance'),
            ('Délice',           NULL, 'https://www.groupedelice.com',   'contact@groupedelice.com',  '+216 71 940 600', 'Groupe agroalimentaire tunisien leader', 'industrie'),
            ('STAFIM Peugeot',   NULL, 'https://www.stafim.com.tn',     'contact@stafim.com.tn',     '+216 71 835 000', 'Concessionnaire automobile Peugeot en Tunisie', 'industrie'),
            ('ESPRIT',           NULL, 'https://www.esprit.tn',         'contact@esprit.tn',         '+216 70 250 000', 'École Supérieure Privée d''Ingénierie et de Technologies', 'education'),
            ('Vermeg',           NULL, 'https://www.vermeg.com',        'contact@vermeg.com',        '+216 71 160 160', 'Éditeur de logiciels spécialisé dans la finance', 'tech'),
            ('Orange Tunisie',   NULL, 'https://www.orange.tn',         'contact@orange.tn',         '+216 21 123 456', 'Opérateur de télécommunications', 'tech'),
            ('Amen Bank',        NULL, 'https://www.amenbank.com.tn',   'contact@amenbank.com.tn',   '+216 71 148 000', 'Banque tunisienne', 'finance')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sponsors');
    }
}
