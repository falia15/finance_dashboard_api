<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename `member` to `householdMember` on household rows: the column is
 * renamed in place, so existing assignments are kept.
 */
final class Version20261023094532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename member_id to household_member_id on income/expense/fixed_expense';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT fk_2d3a8da67597d3fe');
        $this->addSql('DROP INDEX idx_2d3a8da67597d3fe');
        $this->addSql('ALTER TABLE expense RENAME COLUMN member_id TO household_member_id');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA63E4FBC40 FOREIGN KEY (household_member_id) REFERENCES household_member (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_2D3A8DA63E4FBC40 ON expense (household_member_id)');
        $this->addSql('ALTER TABLE fixed_expense DROP CONSTRAINT fk_5a690a007597d3fe');
        $this->addSql('DROP INDEX idx_5a690a007597d3fe');
        $this->addSql('ALTER TABLE fixed_expense RENAME COLUMN member_id TO household_member_id');
        $this->addSql('ALTER TABLE fixed_expense ADD CONSTRAINT FK_5A690A003E4FBC40 FOREIGN KEY (household_member_id) REFERENCES household_member (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_5A690A003E4FBC40 ON fixed_expense (household_member_id)');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT fk_3fa862d07597d3fe');
        $this->addSql('DROP INDEX idx_3fa862d07597d3fe');
        $this->addSql('ALTER TABLE income RENAME COLUMN member_id TO household_member_id');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D03E4FBC40 FOREIGN KEY (household_member_id) REFERENCES household_member (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_3FA862D03E4FBC40 ON income (household_member_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense DROP CONSTRAINT FK_2D3A8DA63E4FBC40');
        $this->addSql('DROP INDEX IDX_2D3A8DA63E4FBC40');
        $this->addSql('ALTER TABLE expense RENAME COLUMN household_member_id TO member_id');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT fk_2d3a8da67597d3fe FOREIGN KEY (member_id) REFERENCES household_member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_2d3a8da67597d3fe ON expense (member_id)');
        $this->addSql('ALTER TABLE fixed_expense DROP CONSTRAINT FK_5A690A003E4FBC40');
        $this->addSql('DROP INDEX IDX_5A690A003E4FBC40');
        $this->addSql('ALTER TABLE fixed_expense RENAME COLUMN household_member_id TO member_id');
        $this->addSql('ALTER TABLE fixed_expense ADD CONSTRAINT fk_5a690a007597d3fe FOREIGN KEY (member_id) REFERENCES household_member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5a690a007597d3fe ON fixed_expense (member_id)');
        $this->addSql('ALTER TABLE income DROP CONSTRAINT FK_3FA862D03E4FBC40');
        $this->addSql('DROP INDEX IDX_3FA862D03E4FBC40');
        $this->addSql('ALTER TABLE income RENAME COLUMN household_member_id TO member_id');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT fk_3fa862d07597d3fe FOREIGN KEY (member_id) REFERENCES household_member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3fa862d07597d3fe ON income (member_id)');
    }
}
