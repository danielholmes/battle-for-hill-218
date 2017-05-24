<?php

require __DIR__ . '/../vendor/autoload.php';

use BGAWorkbench\Commands\WatchCommand;
use BGAWorkbench\Commands\DeployCommand;
use BGAWorkbench\Commands\ValidateCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new DeployCommand());
$application->add(new WatchCommand());
$application->add(new ValidateCommand());
$application->run();