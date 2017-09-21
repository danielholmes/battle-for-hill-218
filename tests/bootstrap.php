<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$vendorDir = __DIR__ . '/../vendor';

require_once($vendorDir . '/autoload.php');
BGAWorkbench\Test\StubProductionEnvironment::stub();
require_once(__DIR__ . '/../battleforhill.game.php');

require_once($vendorDir . '/hamcrest/hamcrest-php/hamcrest/Hamcrest.php');
