<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102180622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recurring_patterns DROP FOREIGN KEY FK_851110C1B03A8386');
        $this->addSql('DROP INDEX IDX_851110C1B03A8386 ON recurring_patterns');
        $this->addSql('ALTER TABLE recurring_patterns ADD frequency VARCHAR(50) NOT NULL, ADD start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD metadata JSON NOT NULL, ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP name, DROP validation_strategy, DROP is_active, CHANGE created_by_id `interval` INT NOT NULL, CHANGE pattern_json days_of_week JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recurring_patterns ADD name VARCHAR(100) NOT NULL, ADD pattern_json JSON NOT NULL, ADD validation_strategy VARCHAR(50) DEFAULT NULL, ADD is_active TINYINT(1) NOT NULL, DROP frequency, DROP start_date, DROP end_date, DROP days_of_week, DROP metadata, DROP updated_at, CHANGE `interval` created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE recurring_patterns ADD CONSTRAINT FK_851110C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_851110C1B03A8386 ON recurring_patterns (created_by_id)');
    }
}
