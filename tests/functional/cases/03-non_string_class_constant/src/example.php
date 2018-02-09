<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase3;

$instance = ServiceLocator::instance(Constants::INTEGER_CONST);
$instance->_getMessage();
$instance->getMessage();
