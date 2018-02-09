<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase4;

class ServiceLocator
{
    /**
     * @param mixed $type
     * @param array $args
     * @return mixed
     * @returnTypeArg $type
     */
    public static function instance($type, array $args = [])
    {
        return new $type(...$args);
    }
}
