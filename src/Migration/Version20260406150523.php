<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406150523 extends AbstractMigration
{
    private const string TABLE_NAME = 'startup_command_runs';

    public function getDescription(): string
    {
        return 'Creates the `startup_command_runs` table.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('executed_at', 'date', ['notnull' => true]);
        $table->addUniqueIndex(['name'], 'uniq_startup_command_name');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TABLE_NAME);
    }
}