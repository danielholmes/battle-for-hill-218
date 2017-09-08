<?php

namespace BGAWorkbench\Commands;

use BGAWorkbench\Commands\BuildStrategy\BuildStrategy;
use BGAWorkbench\Commands\BuildStrategy\CompileBuildStrategy;
use BGAWorkbench\Commands\BuildStrategy\DeployBuildStrategy;
use BGAWorkbench\Project\Project;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use BGAWorkbench\Project\WorkbenchProjectConfig;
use PhpOption\None;
use PhpOption\Some;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Functional as F;

class BuildCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the project')
            ->addOption('watch', 'w', null, 'Watch src files and continually build')
            ->addOption('deploy', 'd', null, 'Deploy files');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = WorkbenchProjectConfig::loadFromCwd();
        $project = $config->loadProject();
        $strategy = $this->createBuildStrategy($input, $config, $project);

        try {
            $strategy->run($output, None::create());
            $output->writeln("<info>Built to {$project->getDistDirectory()->getRelativePathname()}</info>");
        } catch (\Exception $e) {
            $output->write('<error>' . $e->getMessage() . '</error>');
            return -1;
        }

        if (!$input->getOption('watch')) {
            return 0;
        }

        $output->writeln("<info>Watching for changes</info>");
        $this->executeWatch($project, $output, $strategy);
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param WorkbenchProjectConfig $config
     * @param Project $project
     * @return BuildStrategy
     */
    private function createBuildStrategy(InputInterface $input, WorkbenchProjectConfig $config, Project $project)
    {
        $compile = new CompileBuildStrategy($project);
        if ($input->getOption('deploy')) {
            $deployConfig = $config->getDeployConfig()
                ->getOrThrow(new \RuntimeException('No deployment config provided for project'));
            return new DeployBuildStrategy($compile, $deployConfig, $project);
        }

        return $compile;
    }

    /**
     * @param Project $project
     * @param OutputInterface $output
     * @param BuildStrategy $strategy
     */
    private function executeWatch(Project $project, OutputInterface $output, BuildStrategy $strategy)
    {
        $files = new Filesystem();
        $tracker = new Tracker();
        $watcher = new Watcher($tracker, $files);
        $handler = function ($resource, $path) use ($project, $output, $strategy) {
            $output->write('Changed: ' . $path);
            $strategy->run($output, new Some([$project->absoluteToProjectRelativeFile(new \SplFileInfo($path))]));
            $output->writeln(' <info>✓</info>');
        };
        F\each(
            $project->getDevelopmentSourcePaths(),
            function (SplFileInfo $file) use ($watcher, $handler) {
                $listener = $watcher->watch($file->getPathname());
                $listener->onCreate($handler);
                $listener->onModify($handler);
                $listener->onDelete($handler);
            }
        );
        $watcher->start(500000);
    }
}
