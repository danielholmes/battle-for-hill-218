<?php

namespace BGAWorkbench\Commands;

use BGAWorkbench\ProductionDeployment;
use BGAWorkbench\Project\WorkbenchProjectConfig;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class WatchCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('watch')
            ->setDescription('Deploys the project to BGA as changes are made')
            ->setHelp('Deploys each changing file using the information in the project config');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = WorkbenchProjectConfig::loadFromCwd();
        $deployConfig = $config->getDeployConfig()
            ->getOrThrow(new \RuntimeException('No deploy config provided'));

        $project = $config->loadProject();
        $deployment = new ProductionDeployment(
            $deployConfig->getHost(),
            $deployConfig->getUsername(),
            $deployConfig->getPassword(),
            $project->getName()
        );

        $output->writeln('Connecting');
        $deployment->connect();

        $output->writeln('Deploying changed files');
        $deployment->deployChangedFiles(
            $project->getAllFiles(),
            function ($num, $total, SplFileInfo $file) use ($output) {
                $output->writeln("{$num}/{$total} -> {$file->getRelativePathname()} <info>✓</info>");
            }
        );

        $output->writeln('Watching for changes');
        $handler = function ($resource, $path) use ($project, $deployment, $output) {
            // TODO: Run tests and validate before transfer?
            $file = $project->absoluteToProjectRelativeFile(new \SplFileInfo($path));
            $output->write("-> {$file->getRelativePathname()}");
            $deployment->deployFile($file);
            $output->writeln(' <info>✓</info>');
        };
        $files = new Filesystem();
        $tracker = new Tracker();
        $watcher = new Watcher($tracker, $files);
        $project->getDevelopmentLocations()->walk(
            function (SplFileInfo $file) use ($watcher, $project, $deployment, $handler, $output) {
                $listener = $watcher->watch($file->getPathname());
                $listener->onCreate($handler);
                $listener->onModify($handler);
                $listener->onDelete(function ($resource, $path) use ($project, $deployment, $output) {
                    $file = $project->absoluteToProjectRelativeFile(new \SplFileInfo($path));
                    $output->write(">< {$file->getRelativePathname()}");
                    $deployment->remove($file);
                    $output->writeln(' <info>✓</info>');
                });
            }
        );
        $watcher->start(500000);
    }
}
