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
}
