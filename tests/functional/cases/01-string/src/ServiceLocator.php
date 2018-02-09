<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase1;

class AbstractService {}

class ServiceLocator
{
    /**
     * @param string $type
     * @param array $args
     * @return mixed
     * @returnTypeArg $type
     */
    public static function instance($type, array $args = [])
    {
        return new $type(...$args);
    }
}
