<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;
use Torq\PimcoreHelpersBundle\Service\DataGeneration\DataGenerationEngine;

/**
 * Applies config-driven DataGenerationRules on every real add/update of any
 * managed DataObject class. Registered with a config-driven
 * priority by TorqPimcoreHelpersExtension. Mutates in memory only, so no
 * recursive saves; draft auto-saves and version-only saves are skipped.
 */
class DataGenerationListener
{
    public function __construct(
        private readonly DataGenerationEngine $engine,
    ) {
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof Concrete) {
            return;
        }

        $arguments = $event->getArguments();
        if (($arguments['isAutoSave'] ?? false) || ($arguments['saveVersionOnly'] ?? false)) {
            return;
        }

        $this->engine->apply($object);
    }
}
