<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '0.1.0 : Initial schema: user, project, mcp_server with UUID PKs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "user" (id BLOB NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, locale VARCHAR(5) DEFAULT \'en\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('CREATE TABLE project (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, user_id BLOB NOT NULL, CONSTRAINT FK_2FB3D0EEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEA76ED395 ON project (user_id)');

        $this->addSql('CREATE TABLE mcp_server (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, access_token VARCHAR(64) NOT NULL, encrypted_credentials CLOB NOT NULL, created_at DATETIME NOT NULL, project_id BLOB NOT NULL, client_secret VARCHAR(64) NOT NULL, CONSTRAINT FK_301F264C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_301F264CB6A2DD68 ON mcp_server (access_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_301F264C904A356 ON mcp_server (client_secret)');
        $this->addSql('CREATE INDEX IDX_301F264C166D1F9C ON mcp_server (project_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE mcp_server');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE "user"');
    }
}
