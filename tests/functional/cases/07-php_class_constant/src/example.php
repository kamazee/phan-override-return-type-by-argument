<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase7;

use Exception;

$instance = ServiceLocator::instance(Exception::class);
$instance->_getMessage();
$instance->getMessage();
