<?php

use GBAWorkbench\Commands\DeployCommand;

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new DeployCommand());
$application->run();