<?php

namespace Torq\PimcoreHelpersBundle\Model\Asset;

use Pimcore\Model\DataObject\SelectOptions\Traits\EnumTryFromNullableTrait;

enum AssetMetadataType: string
{
    use EnumTryFromNullableTrait;

    case INPUT = 'input';
    case TEXTAREA = 'textarea';
    case ASSET = 'asset';
    case DOCUMENT = 'document';
    case OBJECT = 'object';
    case DATE = 'date';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case WYSIWYG = 'wysiwyg';
    case COUNTRY = 'country';
    case LANGUAGE = 'language';
    case MULTISELECT = 'multiselect';
    case NUMERIC = 'numeric';
    case CALCULATED_VALUE = 'calculatedValue';
    case DATETIME = 'datetime';
    case USER = 'user';
    case MANY_TO_MANY_RELATION = 'manyToManyRelation';
}
