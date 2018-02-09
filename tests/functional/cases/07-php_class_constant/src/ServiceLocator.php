<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase7;

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
