<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument\TestCase4;

$instance = ServiceLocator::instance('\NonExistingClass');
$instance->_getMessage();
$instance->getMessage();
