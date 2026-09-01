<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\User\Permission\Definition;
use Pimcore\Model\Translation;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Generic data index (quick actions) tool permission and translation';
    }

    public function up(Schema $schema): void
    {
        $genericDataIndexToolKey = 'generic_data_index_tool';
        $genericDataIndexTool = Definition::getByKey($genericDataIndexToolKey);

        if (null === $genericDataIndexTool) {
            $permission = new Definition();
            $permission->setKey($genericDataIndexToolKey);
            $permission->setCategory('Pimcore Helpers Permission Group');
            $permission->save();
        }

        $translation = Translation::getByKey($genericDataIndexToolKey, 'admin');
        if (!$translation) {
            $translation = new Translation();
            $translation->setKey($genericDataIndexToolKey);
            $translation->setDomain('admin');
        }

        $translation->addTranslation('en', 'Generic Data Index (Quick Actions) Tool');
        $translation->save();
    }
}
