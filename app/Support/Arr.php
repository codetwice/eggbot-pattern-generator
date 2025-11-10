<?php

namespace App\Support;

class Arr
{
    public static function add(array $array, string $key, mixed $value): array
    {
        if (! array_key_exists($key, $array)) {
            $array[$key] = $value;
        }

        return $array;
    }
}
