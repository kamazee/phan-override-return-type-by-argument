<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument;

use Exception;

class TypeInferenceFailed extends Exception
{
    /**
     * @var string[]
     */
    public $arguments;

    public static function withReason($reason, $arguments = [])
    {
        $e = new self($reason);
        $e->arguments = $arguments;

        return $e;
    }
}
