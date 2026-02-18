<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\AbstractObject;

class DataObjectRepository
{
    use QueryHelpersTrait;

    public function getById(?int $id = null, array $params = [])
    {
        return $id !== null ? AbstractObject::getById($id, $params) : null;
    }

    /**
     * @template-covariant T of AbstractObject
     * @param T $object
     * @return T
     */
    public function save(AbstractObject $object, array $parameters = [])
    {
        return $object->save($parameters);
    }

    public function delete(AbstractObject $object)
    {
        $object->delete();
    }
}
