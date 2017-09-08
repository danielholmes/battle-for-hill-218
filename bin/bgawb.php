<?php

require __DIR__ . '/../vendor/autoload.php';

use BGAWorkbench\Commands\BuildCommand;
use BGAWorkbench\Commands\ValidateCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ValidateCommand());
$application->add(new BuildCommand());
$application->run();