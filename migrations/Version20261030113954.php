<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixed expenses become permanent lines with an active period
 * (`start_year` → `end_year`/`end_month`), and their amounts are deleted
 * with them. Existing lines start the year of their first amount, or the
 * year they were created.
 */
final class Version20261030113954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the active period of fixed_expense, cascade delete its amounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fixed_expense ADD start_year INT DEFAULT NULL');
        $this->addSql('UPDATE fixed_expense f SET start_year = COALESCE((SELECT MIN(a.year) FROM fixed_expense_amount a WHERE a.fixed_expense_id = f.id), EXTRACT(YEAR FROM f.created_at))');
        $this->addSql('ALTER TABLE fixed_expense ALTER start_year SET NOT NULL');
        $this->addSql('ALTER TABLE fixed_expense ADD end_year INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fixed_expense ADD end_month INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fixed_expense_amount DROP CONSTRAINT fk_e69b4e5da8c0f41c');
        $this->addSql('ALTER TABLE fixed_expense_amount ADD CONSTRAINT FK_E69B4E5DA8C0F41C FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expense (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fixed_expense DROP start_year');
        $this->addSql('ALTER TABLE fixed_expense DROP end_year');
        $this->addSql('ALTER TABLE fixed_expense DROP end_month');
        $this->addSql('ALTER TABLE fixed_expense_amount DROP CONSTRAINT FK_E69B4E5DA8C0F41C');
        $this->addSql('ALTER TABLE fixed_expense_amount ADD CONSTRAINT fk_e69b4e5da8c0f41c FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expense (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
