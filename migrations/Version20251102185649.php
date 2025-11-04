<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102185649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3D86A4BF8');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3E92F8F78');
        $this->addSql('DROP INDEX IDX_6000B0D3E92F8F78 ON notifications');
        $this->addSql('DROP INDEX IDX_6000B0D3D86A4BF8 ON notifications');
        $this->addSql('ALTER TABLE notifications ADD user_id INT NOT NULL, ADD recipient_email VARCHAR(255) NOT NULL, ADD metadata JSON NOT NULL, DROP recipient_id, DROP related_reservation_id, DROP retry_count, DROP scheduled_at, CHANGE type type VARCHAR(100) NOT NULL, CHANGE message body LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('DROP INDEX IDX_6000B0D3A76ED395 ON notifications');
        $this->addSql('ALTER TABLE notifications ADD related_reservation_id INT DEFAULT NULL, ADD retry_count INT NOT NULL, ADD scheduled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP recipient_email, DROP metadata, CHANGE type type VARCHAR(20) NOT NULL, CHANGE user_id recipient_id INT NOT NULL, CHANGE body message LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3D86A4BF8 FOREIGN KEY (related_reservation_id) REFERENCES reservations (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_6000B0D3E92F8F78 ON notifications (recipient_id)');
        $this->addSql('CREATE INDEX IDX_6000B0D3D86A4BF8 ON notifications (related_reservation_id)');
    }
}
