<?php

namespace Torq\PimcoreHelpersBundle\Service\Utility;

use Carbon\Carbon;

class ArrayUtils
{
    /** @param string|string[] $key */
    public function get(array|string $key, ?array $array, mixed $default = null)
    {
        if (empty($array)) {
            return $default;
        }

        if (is_array($key)) {
            if (empty($key)) {
                throw new \InvalidArgumentException(
                    'You must supply a key to check for the given array (empty array of keys given)'
                );
            } elseif (count($key) === 1) {
                return $this->get($key[0], $array);
            } else {
                return key_exists($key[0], $array) && is_array($array[$key[0]])
                    ? $this->get(array_slice($key, 1), $array[$key[0]])
                    : $default;
            }
        } else {
            return key_exists($key, $array) ? $array[$key] : $default;
        }
    }

    /** @param string|string[] $key */
    public function getDate(array|string $key, ?array $array, mixed $default = null)
    {
        $val = $this->get($key, $array, $default);
        return $val ? Carbon::parse($val) : $default;
    }

    public function getInt(array|string $key, ?array $array, mixed $default = null)
    {
        $val = $this->get($key, $array, $default);
        return empty($val) ? $default : intval($val);
    }

    public function getFloat(array|string $key, ?array $array, mixed $default = null)
    {
        $val = $this->get($key, $array, $default);
        return empty($val) ? $default : floatval($val);
    }

    /**
     * Get first item from array matched by `fn`, remove that item from array.
     * @param array $array of type `item`
     * @param callable $fn with signature fn(item): bool
     * @return array{0: mixed|null, 1: array} the first item matched by `fn` (or null) and the array with that item removed (or not).
     */
    public function findAndRemove(array $array, callable $fn): array
    {
        if (($index = $this->findIndex($fn, $array)) !== -1) {
            $item = $array[$index];
            array_splice($array, $index, 1);
            return [$item, $array];
        } else {
            return [null, $array];
        }
    }

    /**
     * @param array $array of type `item`
     * @param callable $fn with signature fn(item): bool
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
        $index = $this->findIndex($callable, $array);
        return $index != -1 ? $array[$index] : null;
    }

    public function findIndex(callable $callable, ?array $array)
    {
        if (empty($array)) {
            return -1;
        }

        foreach ($array as $index => $option) {
            if ($callable($option)) {
                return $index;
            }
        }

        return -1;
    }
}
