<?php

require __DIR__ . '/../vendor/autoload.php';

use GBAWorkbench\Commands\WatchCommand;
use GBAWorkbench\Commands\DeployCommand;
use GBAWorkbench\Commands\ValidateCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new DeployCommand());
$application->add(new WatchCommand());
$application->add(new ValidateCommand());
$application->run();