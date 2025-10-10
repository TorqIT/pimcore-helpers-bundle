<?php

namespace Torq\PimcoreHelpersBundle\Model\Common;

use Symfony\Component\Serializer\Context\Normalizer\AbstractNormalizerContextBuilder;
use Symfony\Component\Serializer\Encoder\JsonEncode;

final class HelperContextBuilder extends AbstractNormalizerContextBuilder
{
    // Common
    public const string LANGUAGE = 'language';
    public const string DATE_FORMAT = 'dateFormat';

    // Object
    public const string INCLUDE_CHILDREN = 'includeChildren';
    public const string CHILD_TYPES = 'childTypes';
    public const string INHERIT_VALUES = 'inheritValues';

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

    public function includeChildren(bool $includeChildren)
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

    public function useThumbnail(string $configName)
    {
        return $this->with(self::THUMBNAIL, $configName);
    }

    public function isValueSerialized(bool $isSerialized)
    {
        return $this->with(self::IS_VALUE_SERIALIZED, $isSerialized);
    }

    public function withAlpha(bool $withAlpha)
    {
        return $this->with(self::WITH_ALPHA, $withAlpha);
    }

    public function withHash(bool $withHash)
    {
        return $this->with(self::WITH_HASH, $withHash);
    }
}