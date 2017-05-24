<?php

namespace GBAWorkbench\Commands;

use GBAWorkbench\ProductionDeployment;
use GBAWorkbench\Project;
use GBAWorkbench\ProjectWorkbenchConfig;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setHelp('Deploys each changing file using the information in bgaproject.json');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = Project::loadFrom(new \SplFileInfo(getcwd()));
        $config = ProjectWorkbenchConfig::loadFrom($project);
        $deployment = new ProductionDeployment(
            $config->getSftpHost(),
            $config->getSftpUsername(),
            $config->getSftpPassword(),
            $project->getName()
        );

        $output->writeln('Connecting');
        $deployment->connect();

        $output->writeln('Watching for changes');
        $handler = function($resource, $path) use ($project, $deployment, $output) {
            $file = $project->getFile($path);
            $output->write("-> {$file->getRelativePathname()}");
            $deployment->deployFile($file, $file->getRelativePathname());
            $output->writeln(' ✓');
        };
        $files = new Filesystem();
        $tracker = new Tracker();
        $watcher = new Watcher($tracker, $files);
        foreach ($project->getAllFiles() as $file) {
            $listener = $watcher->watch($file->getPathname());
            $listener->onCreate($handler);
            $listener->onModify($handler);
        }
        $watcher->start();
    }
}