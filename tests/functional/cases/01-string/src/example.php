<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase1;

$instance = ServiceLocator::instance('\Exception');
$instance->_getMessage();
$instance->getMessage();
