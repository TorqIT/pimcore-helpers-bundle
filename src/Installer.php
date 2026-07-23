<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service as ClassDefinitionService;

/**
 * Installs the DataGenerationRule DataObject class shipped with the bundle
 * Mirrors PortalEngine's installer: the class definition ships
 * as a JSON resource and is imported into the host var/classes on bundle install;
 * pimcore:deployment:classes-rebuild then reconciles the DB tables.
 */
class Installer extends AbstractInstaller
{
    private const CLASS_NAME = 'DataGenerationRule';
    private const CLASS_GROUP = 'Data Generation';

    public function install(): void
    {
        $this->installClass(
            self::CLASS_NAME,
            dirname(__DIR__) . '/install/class_source/class_DataGenerationRule_export.json',
        );
    }

    public function isInstalled(): bool
    {
        return ClassDefinition::getByName(self::CLASS_NAME) !== null;
    }

    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    public function canBeUninstalled(): bool
    {
        return false;
    }

    private function installClass(string $classname, string $filepath): void
    {
        if (!file_exists($filepath)) {
            return;
        }

        if (ClassDefinition::getByName($classname) !== null) {
            return;
        }

        $class = new ClassDefinition();
        $class->setName($classname);
        $class->setGroup(self::CLASS_GROUP);

        ClassDefinitionService::importClassDefinitionFromJson($class, (string) file_get_contents($filepath));
    }
}
