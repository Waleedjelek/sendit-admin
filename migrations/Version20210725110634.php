<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210725110634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sendit_countries (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', code VARCHAR(10) NOT NULL, name VARCHAR(255) NOT NULL, flag VARCHAR(255) NOT NULL, active TINYINT(1) DEFAULT \'1\' NOT NULL, UNIQUE INDEX UNIQ_EAD0889977153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sendit_user_tokens (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', token VARCHAR(150) NOT NULL, active TINYINT(1) DEFAULT \'1\' NOT NULL, created_date DATETIME NOT NULL, expiry_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_A0997E105F37A13B (token), INDEX IDX_A0997E10A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sendit_users (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', email VARCHAR(180) NOT NULL, email_verified TINYINT(1) DEFAULT \'0\' NOT NULL, email_verification_token VARCHAR(100) DEFAULT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, mobile_number VARCHAR(100) DEFAULT NULL, mobile_verified TINYINT(1) DEFAULT \'0\' NOT NULL, mobile_verification_code VARCHAR(10) DEFAULT NULL, role VARCHAR(100) DEFAULT NULL, password VARCHAR(100) NOT NULL, password_reset_token VARCHAR(100) DEFAULT NULL, active TINYINT(1) DEFAULT \'1\' NOT NULL, created_date DATETIME NOT NULL, last_login_date DATETIME DEFAULT NULL, modified_date DATETIME DEFAULT NULL, password_reset_token_date DATETIME DEFAULT NULL, email_verification_token_date DATETIME DEFAULT NULL, mobile_verification_code_date DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_3FD9EC04E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sendit_user_tokens ADD CONSTRAINT FK_A0997E10A76ED395 FOREIGN KEY (user_id) REFERENCES sendit_users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sendit_user_tokens DROP FOREIGN KEY FK_A0997E10A76ED395');
        $this->addSql('DROP TABLE sendit_countries');
        $this->addSql('DROP TABLE sendit_user_tokens');
        $this->addSql('DROP TABLE sendit_users');
    }
}
