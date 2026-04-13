<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreFieldDef;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationstoreService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\AbstractQuantityValue;
use Pimcore\Model\Element\ElementInterface;

/**
 * Grid column operator that retrieves a classification store value by key,
 * searching across all groups — returning the first non-empty value found.
 */
final class ClassificationStoreKeyGetter extends AbstractOperator
{
    private string $csFieldName;

    private int $keyId;

    private string $keyName;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->csFieldName = $config->csFieldName ?? '';
        $this->keyId = (int)($config->keyId ?? 0);
        $this->keyName = $config->keyName ?? '';
    }

    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = null;
        $result->isEmpty = true;

        if (!$this->csFieldName || !$this->keyId || !($element instanceof Concrete)) {
            return $result;
        }

        $getter = 'get' . ucfirst($this->csFieldName);
        if (!method_exists($element, $getter)) {
            return $result;
        }

        $csData = $element->$getter();
        if (!$csData instanceof Classificationstore) {
            return $result;
        }

        // Determine the language to use. Non-localized stores use 'default'.
        $csLanguage = 'default';
        $csFieldDef = $element->getClass()->getFieldDefinition($this->csFieldName);
        if ($csFieldDef instanceof ClassificationstoreFieldDef && $csFieldDef->isLocalized()) {
            $csLanguage = $this->context['language'] ?? 'default';
        }

        // Load key config once (cached by Pimcore) to get the field definition for empty-checks.
        $keyConfig = KeyConfig::getById($this->keyId);
        if (!$keyConfig) {
            return $result;
        }

        $fieldDef = ClassificationstoreService::getFieldDefinitionFromKeyConfig($keyConfig);

        // Iterate only over groups that actually have stored data for this key.
        $items = $csData->getItems();
        foreach ($items as $groupId => $groupItems) {
            if (!array_key_exists($this->keyId, $groupItems)) {
                continue;
            }

            $value = $csData->getLocalizedKeyValue((int)$groupId, $this->keyId, $csLanguage, false, false);

            if ($fieldDef && !$fieldDef->isEmpty($value)) {
                $result->value = $value instanceof AbstractQuantityValue ? (string)$value : $value;
                $result->isEmpty = false;

                return $result;
            }
        }

        return $result;
    }
}
