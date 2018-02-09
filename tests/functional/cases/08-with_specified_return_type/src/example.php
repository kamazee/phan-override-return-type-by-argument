<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase8;

use Exception;

$instance = ServiceLocator::instance(Exception::class);
$instance->_getMessage();
$instance->getMessage();
