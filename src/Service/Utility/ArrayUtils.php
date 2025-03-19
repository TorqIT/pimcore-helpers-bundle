<?php

namespace Torq\PimcoreHelpersBundle\Service\Utility;

use Carbon\Carbon;
use Pimcore\Model\DataObject\AbstractObject;

class ArrayUtils
{
    /**
     * Build AbstractObject or AbstractData
     * @param AbstractObject $object
     * @param array $body
     * @return AbstractObject|AbstractData
     */
    public static function setDataByArray(AbstractObject $object, array $body): AbstractObject
    {
        foreach ($body as $key => $value) {
            $setter = "set" . $key;
            $object->$setter($value);
        }
        return $object;
    }

    /** @param string|string[] $key */
    public static function safeVal(array|string $key, array $array)
    {
        if (is_array($key)) {
            if (empty($key)) {
                throw new \InvalidArgumentException("You must supply a key to check for the given array (empty array of keys given)");
            } else if (count($key) === 1) {
                return self::safeVal($key[0], $array);
            }
            else
            {
                return key_exists($key[0], $array) && is_array($array[$key[0]]) ?
                    self::safeVal(array_slice($key, 1), $array[$key[0]]) :
                    null;
            }
        } else {
            return key_exists($key, $array) ? $array[$key] : null;
        }
    }

    /** @param string|string[] $key */
    public static function safeDate(array|string $key, array $array)
    {
        $val = self::safeVal($key, $array);
        return $val ? Carbon::parse($val) : null;
    }

    public static function safeInt(array|string $key, array $array)
    {
        $val = self::safeVal($key, $array);
        return empty($val) ? null : intval($val);
    }

    public static function safeFloat(array|string $key, array $array)
    {
        $val = self::safeVal($key, $array);
        return empty($val) ? null : floatval($val);
    }

    public static function getFirstItemSafely(?array $array)
    {
        return ($array && count($array) > 0) ? $array[0] : null;
    }

    public static function findInArray(callable $callable, array $array)
    {
        $index = self::findIndex($callable, $array);
        return $index != -1 ? $array[$index] : null;
    }

    public static function findIndex(callable $callable, array $array)
    {
        foreach($array as $index => $option)
        {
            if($callable($option))
            {
                return $index;
            }
        }

        return -1;
    }
}
