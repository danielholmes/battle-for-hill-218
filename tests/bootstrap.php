<?php

$vendorDir = __DIR__ . '/../vendor';
require_once($vendorDir . '/autoload.php');
require_once($vendorDir . '/hamcrest/hamcrest-php/hamcrest/Hamcrest.php');

// Stub for production PHP environment
define('APP_GAMEMODULE_PATH', __DIR__ . '/../src/BGAWorkbench/Stubs/');
require_once(APP_GAMEMODULE_PATH . 'framework.php');