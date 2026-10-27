<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Translation;
use Pimcore\Model\User\Permission\Definition;

final class Version20261027121749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create QuickAction permission.';
    }

    public function up(Schema $schema): void
    {
        $key = 'quick_actions';
        if (empty(Definition::getByKey($key))) {
            $permission = new Definition();
            $permission->setKey($key);
            $permission->setCategory('Pimcore Helpers Permission Group');
            $permission->save();
        }
    }
}
