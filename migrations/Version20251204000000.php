<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table';
    }

    public function up(Schema $schema): void
    {
        $tbl = $schema->createTable('users');
        $tbl->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $tbl->addColumn('email', Types::STRING, ['length' => 180]);
        $tbl->addColumn('roles', Types::JSON);
        $tbl->addColumn('password', Types::STRING, ['length' => 255]);
        $tbl->addColumn('name', Types::STRING, ['length' => 255]);
        $tbl->addColumn('status', Types::STRING, ['length' => 20]);
        $tbl->addColumn('registration_time', Types::DATETIME_MUTABLE);
        $tbl->addColumn('last_login_time', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $tbl->setPrimaryKey(['id']);
        $tbl->addUniqueIndex(['email'], 'UNIQ_IDENTIFIER_EMAIL');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}
