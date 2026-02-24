<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\Concrete;

class DataObjectRepository
{
    use QueryHelpersTrait;

    public function getById(?int $id = null, array $params = [])
    {
        return $id !== null ? Concrete::getById($id, $params) : null;
    }

    /**
     * @template-covariant T of Concrete
     * @param T $object
     * @return T
     */
    public function save(Concrete $object, array $parameters = [])
    {
        return $object->save($parameters);
    }

    /**
     * @template-covariant T of Concrete
     * @param T $object
     * @return T
     */
    public function saveScheduledTasks(Concrete $object)
    {
        $object->saveScheduledTasks();
        return $object;
    }

    public function delete(Concrete $object)
    {
        $object->delete();
    }
}
