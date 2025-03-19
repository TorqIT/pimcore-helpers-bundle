<?php

namespace Torq\PimcoreHelpersBundle\Service\Repository;

use Pimcore\Model\DataObject\Concrete;

abstract class DataObjectRepository
{
    public function save(Concrete $dataObject)
    {
        $dataObject->save();
    }

    public function delete(Concrete $dataObject)
    {
        $dataObject->delete();
    }
}
