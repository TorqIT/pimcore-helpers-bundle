<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\AbstractObject;

class DataObjectRepository
{
    public function getById(?int $id)
    {
        return $id !== null ? AbstractObject::getById($id) : null;
    }

    /**
     * @template-covariant T of AbstractObject
     * @param T $object
     * @return T
     */
    public function save(AbstractObject $object)
    {
        return $object->save();
    }

    public function delete(AbstractObject $object)
    {
        $object->delete();
    }
}
