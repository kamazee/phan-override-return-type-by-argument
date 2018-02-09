<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase8;

class AbstractService {}

class ServiceLocator
{
    /**
     * @param string $type
     * @param array $args
     * @return AbstractService
     * @returnTypeArg $type
     */
    public static function instance($type, array $args = [])
    {
        return new $type(...$args);
    }
}
