<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\Element\ValidationException;

class FreeSoloField extends Select
{
    public function getFieldType(): string
    {
        return 'freeSolo';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }
}
