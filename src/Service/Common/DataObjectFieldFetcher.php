<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use ReflectionClass;
use ReflectionClassConstant;

/** Service which fetches the fields from a given Pimcore object class */
class DataObjectFieldFetcher
{
    /** @return string[] */
    public function getFieldsFromObject(string $objectClass, bool $includeId = true): array
    {
        $constants = (new ReflectionClass($objectClass))->getConstants(ReflectionClassConstant::IS_PUBLIC);
        $fields = array_filter($constants, fn(string $key) => str_starts_with($key, 'FIELD'), ARRAY_FILTER_USE_KEY);
        return $includeId ? ['FIELD_ID' => 'id', ...$fields] : $fields;
    }
}
