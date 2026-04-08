<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create solution_traitement_vote table to allow only one vote per user and treatment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE solution_traitement_vote (id INT AUTO_INCREMENT NOT NULL, solution_traitement_id INT NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_6D76D7A7424E4E4B (solution_traitement_id), INDEX IDX_6D76D7A4FB88E14F (utilisateur_id), UNIQUE INDEX uniq_solution_user_vote (solution_traitement_id, utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE solution_traitement_vote ADD CONSTRAINT FK_6D76D7A7424E4E4B FOREIGN KEY (solution_traitement_id) REFERENCES solution_traitement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solution_traitement_vote ADD CONSTRAINT FK_6D76D7A4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solution_traitement_vote DROP FOREIGN KEY FK_6D76D7A7424E4E4B');
        $this->addSql('ALTER TABLE solution_traitement_vote DROP FOREIGN KEY FK_6D76D7A4FB88E14F');
        $this->addSql('DROP TABLE solution_traitement_vote');
    }
}
