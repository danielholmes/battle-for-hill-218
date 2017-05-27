<?php

namespace BGAWorkbench\Commands;

use BGAWorkbench\Project;
use BGAWorkbench\ProjectWorkbenchConfig;
use BGAWorkbench\StateConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class ValidateCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('validate')
            ->setDescription('Validates that the BGA project is valid')
            ->setHelp('Runs various checks on the BGA project such as valid state configuration, valid php syntax and all required files');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = ProjectWorkbenchConfig::loadFromCwd();
        $project = $config->loadProject();

        $this->validateRequiredFilesExist($project);
        // TODO: Lint all php files
        $this->validateStates($project);
    }

    /**
     * @param Project $project
     */
    private function validateRequiredFilesExist(Project $project)
    {
        $notFoundList = $project->getRequiredFiles()
            ->filter(function (SplFileInfo $file) { return !$file->isFile(); })
            ->sort()
            ->map(function(SplFileInfo $file) { return $file->getRelativePathname(); })
            ->join(', ');
        if (!empty($notFoundList)) {
            throw new \RuntimeException("Missing required files: {$notFoundList}");
        }
    }

    /**
     * @param Project $project
     */
    private function validateStates(Project $project)
    {
        require_once(__DIR__ . '/../Stubs/framework.php');
        $variableName = 'machinestates';
        $fileName = 'states.inc.php';
        $states = $project->getFileVariableValue($fileName, $variableName)
            ->getOrThrow(new \RuntimeException("Expect variable {$variableName} in {$fileName}"));

        $processor = new Processor();
        $validated = $processor->processConfiguration(new StateConfiguration(), array($states));
        $stateIds = array_keys($validated);

        array_walk(
            $validated,
            function(array $state) use ($stateIds) {
                if (!isset($state['transitions'])) {
                    return;
                }
                $transitionToIds = array_values($state['transitions']);
                $differentIds = array_diff($transitionToIds, $stateIds);
                if (!empty($differentIds)) {
                    $diff = join(', ', $differentIds);
                    throw new \RuntimeException("State {$state['name']} has transition to non existent state id(s) {$diff}");
                }
            }
        );
    }
}