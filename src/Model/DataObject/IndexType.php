<?php

namespace Torq\PimcoreHelpersBundle\Model\DataObject;

/** The Pimcore table an index lives on, matching the `index_type` values of class composite indices. */
enum IndexType: string
{
    case QUERY = 'query';
    case STORE = 'store';
    case LOCALIZED_QUERY = 'localized_query';
    case LOCALIZED_STORE = 'localized_store';

    public function isQuery(): bool
    {
        return $this === self::QUERY || $this === self::LOCALIZED_QUERY;
    }

    public function isLocalized(): bool
    {
        return $this === self::LOCALIZED_QUERY || $this === self::LOCALIZED_STORE;
    }
}
