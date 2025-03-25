<?php

namespace Torq\PimcoreHelpersBundle\Service\Utility;

use Carbon\Carbon;

class ArrayUtils
{
    /** @param string|string[] $key */
    public function get(array|string $key, ?array $array)
    {
        if(empty($array))
        {
            return null;
        }

        if (is_array($key)) {
            if (empty($key)) {
                throw new \InvalidArgumentException("You must supply a key to check for the given array (empty array of keys given)");
            } else if (count($key) === 1) {
                return self::get($key[0], $array);
            }
            else
            {
                return key_exists($key[0], $array) && is_array($array[$key[0]]) ?
                    self::get(array_slice($key, 1), $array[$key[0]]) :
                    null;
            }
        } else {
            return key_exists($key, $array) ? $array[$key] : null;
        }
    }

    /** @param string|string[] $key */
    public function getDate(array|string $key, ?array $array)
    {
        $val = self::get($key, $array);
        return $val ? Carbon::parse($val) : null;
    }

    public function getInt(array|string $key, ?array $array)
    {
        $val = self::get($key, $array);
        return empty($val) ? null : intval($val);
    }

    public function getFloat(array|string $key, ?array $array)
    {
        $val = self::get($key, $array);
        return empty($val) ? null : floatval($val);
    }

    /**
     * @param array $array of type `item`
     * @param callable $fn with signature fn(item): bool
     * @return bool
     */
    public function any(array $array, callable $fn): bool
    {
        foreach ($array as $key => $value) {
            if ($fn($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $array of type `item`
     * @param callable $fn with signature fn(item, key?): bool
     * @return bool
     */
    public function all(array $array, callable $fn): bool
    {
        foreach ($array as $key => $value) {
            if (!$fn($value, $key)) {
                return false;
            }
        }
        return true;
    }

    public function findInArray(callable $callable, ?array $array)
    {
        $index = self::findIndex($callable, $array);
        return $index != -1 ? $array[$index] : null;
    }

    public function findIndex(callable $callable, ?array $array)
    {
        if(empty($array))
        {
            return -1;
        }

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
