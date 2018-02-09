<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase6;

$instance = ServiceLocator::instance(Constants::TYPE);
$instance->_getMessage();
$instance->getMessage();
