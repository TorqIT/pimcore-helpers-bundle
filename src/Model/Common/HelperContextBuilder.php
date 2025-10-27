<?php

namespace Torq\PimcoreHelpersBundle\Model\Common;

use Symfony\Component\Serializer\Context\Normalizer\AbstractNormalizerContextBuilder;
use Symfony\Component\Serializer\Encoder\JsonEncode;

final class HelperContextBuilder extends AbstractNormalizerContextBuilder
{
    // Common
    public const string LANGUAGE = 'language';
    public const string DATE_FORMAT = 'dateFormat';
    public const string EMPTY_ARRAYS_AS_NULL = 'emptyArraysAsNull';

    // Object
    public const string INCLUDE_CHILDREN = 'includeChildren';
    public const string CHILD_TYPES = 'childTypes';
    public const string INHERIT_VALUES = 'inheritValues';
    public const string SKIP_NULL_VALUES = 'skipNullValues';
    public const string INCLUDE_ID = 'includeId';
    public const string EXCLUDED_FIELDS = 'excludedFields';
    public const string INCLUDED_FIELD_TYPES = 'includedFieldTypes';
    public const string EXCLUDED_FIELD_TYPES = 'excludedFieldTypes';

    // Classification Store
    public const GROUP_FILTER = 'groupFilter';
    public const KEY_FILTERS = 'keyFilter';

    // Asset
    public const THUMBNAIL = 'thumbnail';
    public const IS_VALUE_SERIALIZED = 'isValueSerialized';

    // Color
    public const string WITH_ALPHA = 'withAlpha';
    public const string WITH_HASH = 'withHash';

    public static function create()
    {
        return new self();
    }

    public function set(string $key, mixed $value)
    {
        return $this->with($key, $value);
    }

    public function withUnescapedSlashes()
    {
        return $this->with(JsonEncode::OPTIONS, JSON_UNESCAPED_SLASHES);
    }

    /**
     * * @param string $language use the Pimcore language/locale values, e.g. en_US for English (US),
     * fr_FR for French (France), etc.
     * */
    public function withLanguage(string $language)
    {
        return $this->with(self::LANGUAGE, $language);
    }

    /**
     * @param string $dateFormat Valid Carbon date format, as well as `"ISO8601"` which will call the
     * `toIso8601String()` method.
     */
    public function withDateFormat(string $dateFormat)
    {
        return $this->with(self::DATE_FORMAT, $dateFormat);
    }

    public function castEmptyArraysToNull(bool $castToNull = true)
    {
        return $this->with(self::EMPTY_ARRAYS_AS_NULL, $castToNull);
    }

    public function includeChildren(bool $includeChildren = true)
    {
        return $this->with(self::INCLUDE_CHILDREN, $includeChildren);
    }

    /** @param string[] $childTypes possible types are: folder, object, variant */
    public function withChildTypes(array $childTypes)
    {
        return $this->with(self::CHILD_TYPES, $childTypes);
    }

    public function inheritValues(bool $inheritValues)
    {
        return $this->with(self::INHERIT_VALUES, $inheritValues);
    }

    public function skipNullValues(bool $skipNullValues = true)
    {
        return $this->with(self::SKIP_NULL_VALUES, $skipNullValues);
    }

    public function includeId(bool $includeId = true)
    {
        return $this->with(self::INCLUDE_ID, $includeId);
    }

    /** @param string[] $excludedFields */
    public function excludeFields(array $excludedFields)
    {
        return $this->with(self::EXCLUDED_FIELDS, $excludedFields);
    }

    public function addExcludedField(string $field)
    {
        $existingExclusion = $this->toArray()[self::EXCLUDED_FIELDS] ?? [];
        return $this->with(self::EXCLUDED_FIELDS, [...$existingExclusion, $field]);
    }

    /** @param string[] $includedFieldTypes */
    public function includeFieldTypes(array $includedFieldTypes)
    {
        return $this->with(self::INCLUDED_FIELD_TYPES, $includedFieldTypes);
    }

    public function addIncludedFieldType(string $type)
    {
        $existingInclusions = $this->toArray()[self::INCLUDED_FIELD_TYPES] ?? [];
        return $this->with(self::INCLUDED_FIELD_TYPES, [...$existingInclusions, $type]);
    }

    /** @param string[] $excludedFieldTypes */
    public function excludeFieldTypes(array $excludedFieldTypes)
    {
        return $this->with(self::EXCLUDED_FIELD_TYPES, $excludedFieldTypes);
    }

    public function addExcludedFieldType(string $type)
    {
        $existingExclusions = $this->toArray()[self::EXCLUDED_FIELD_TYPES] ?? [];
        return $this->with(self::EXCLUDED_FIELD_TYPES, [...$existingExclusions, $type]);
    }

    public function withGroupFilter(callable $fn) {
        return $this->with(self::GROUP_FILTER, $fn);
    }

    public function withKeyFilter(callable $fn) {
        return $this->with(self::KEY_FILTERS, $fn);
    }

    public function useThumbnail(string $configName)
    {
        return $this->with(self::THUMBNAIL, $configName);
    }

    public function valuesAreSerialized(bool $isSerialized = true)
    {
        return $this->with(self::IS_VALUE_SERIALIZED, $isSerialized);
    }

    public function includeAlpha(bool $withAlpha = true)
    {
        return $this->with(self::WITH_ALPHA, $withAlpha);
    }

    public function includeHash(bool $withHash = true)
    {
        return $this->with(self::WITH_HASH, $withHash);
    }
}