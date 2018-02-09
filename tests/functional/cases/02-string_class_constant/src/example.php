<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase2;

$instance = ServiceLocator::instance(Constants::TYPE);
$instance->_getMessage();
$instance->getMessage();
