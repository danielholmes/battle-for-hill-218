<?php

namespace BGAWorkbench\Commands;

use BGAWorkbench\ProductionDeployment;
use BGAWorkbench\ProjectWorkbenchConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class DeployCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploys the project to BGA')
            ->setHelp('Deploys all project files using the information in bgaproject.json');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cwd = new \SplFileInfo(getcwd());
        $config = ProjectWorkbenchConfig::loadFrom($cwd);
        $project = $config->loadProject();

        $deployment = new ProductionDeployment(
            $config->getSftpHost(),
            $config->getSftpUsername(),
            $config->getSftpPassword(),
            $project->getName()
        );

        $output->writeln('Connecting');
        $deployment->connect();

        $output->writeln('Determining changed files');
        $total = $deployment->deployChangedFiles(
            $project->getAllFiles(),
            function ($num, $total, SplFileInfo $file) use ($output) {
                $output->writeln("{$num}/{$total} -> {$file->getRelativePathname()}");
            }
        );
        $output->writeln("Done: {$total} file(s) transferred");
    }
}