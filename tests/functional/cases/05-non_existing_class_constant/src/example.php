<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase5;

$instance = ServiceLocator::instance(Exception::TEST);
$instance->_getMessage();
$instance->getMessage();
