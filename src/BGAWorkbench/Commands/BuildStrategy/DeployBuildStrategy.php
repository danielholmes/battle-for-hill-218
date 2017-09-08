<?php

namespace BGAWorkbench\Commands\BuildStrategy;

use BGAWorkbench\ProductionDeployment;
use BGAWorkbench\Project\DeployConfig;
use BGAWorkbench\Project\Project;
use PhpOption\Option;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class DeployBuildStrategy implements BuildStrategy
{
    /**
     * @var BuildStrategy
     */
    private $beforeStrategy;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var ProductionDeployment
     */
    private $deployment;

    /**
     * @param BuildStrategy $beforeStrategy
     * @param DeployConfig $deployConfig
     * @param Project $project
     */
    public function __construct(BuildStrategy $beforeStrategy, DeployConfig $deployConfig, Project $project)
    {
        $this->beforeStrategy = $beforeStrategy;
        $this->deployment = new ProductionDeployment(
            $deployConfig->getHost(),
            $deployConfig->getUsername(),
            $deployConfig->getPassword(),
            $project->getName()
        );
        $this->project = $project;
    }

    private function ensureDeploymentConnected()
    {
        if ($this->deployment->isConnected()) {
            return;
        }
        $this->deployment->connect();
    }

    /**
     * @inheritdoc
     */
    public function run(OutputInterface $output, Option $changedFiles)
    {
        $beforeChangedFiles = $this->beforeStrategy->run($output, $changedFiles);
        $this->ensureDeploymentConnected();

        $outputCallback = function ($num, $total, SplFileInfo $file) use ($output) {
            $output->writeln("{$num}/{$total} -> {$file->getRelativePathname()}");
        };
        if (empty($beforeChangedFiles)) {
            die('TODO: gather all files in dist, shouldnt be beforeChangedFiles');
            $this->deployment->deployChangedFiles($beforeChangedFiles, $outputCallback);
        } else {
            $this->deployment->deployFiles($beforeChangedFiles, $outputCallback);
        }

        return $beforeChangedFiles;
    }
}
