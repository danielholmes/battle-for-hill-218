<?php

namespace GBAWorkbench\Commands;

use GBAWorkbench\Project;
use GBAWorkbench\ProjectWorkbenchConfig;
use phpseclib\Net\SFTP;
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
            ->setHelp('Deploys all project files using the information in config/bgaproject.ini');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = Project::loadFrom(getcwd());
        $config = ProjectWorkbenchConfig::loadFrom($project);

        $sftp = new SFTP($config->getSftpHost());
        if (!$sftp->login($config->getSftpUsername(), $config->getSftpPassword())) {
            $output->writeln('Login to BGA Server failed');
            return -1;
        }

        $remoteMTimes = $this->getMTimesByFilepath($sftp->rawlist($project->getName(), true));
        $allFiles = $project->getAllFiles();
        $newerFiles = array_values(
            array_filter(
                $allFiles,
                function(SplFileInfo $file) use ($remoteMTimes) {
                    return !isset($remoteMTimes[$file->getRelativePathname()]) ||
                        $remoteMTimes[$file->getRelativePathname()] < $file->getMTime();
                }
            )
        );
        foreach ($newerFiles as $i => $file) {
            $remotePathname = sprintf('%s/%s', $project->getName(), $file->getRelativePathname());
            $output->writeln(sprintf(
                '%d/%d %s -> %s',
                $i + 1,
                count($newerFiles),
                $file->getRelativePathname(), $remotePathname
            ));
            if (!$sftp->put($remotePathname, $file->getPathname(), SFTP::SOURCE_LOCAL_FILE)) {
                $output->writeln('Error ftping file');
                return 1;
            }
        }

        $output->writeln(sprintf('Done: %d file(s) transferred', count($newerFiles)));
    }

    /**
     * @param array $rawRemoteList
     * @return array
     */
    private function getMTimesByFilepath(array $rawRemoteList)
    {
        $map = array();
        foreach ($rawRemoteList as $key => $value) {
            if ($key === '.' || $key === '..') {
                continue;
            }

            if (is_array($value)) {
                $subMTimes = $this->getMTimesByFilepath($value);
                foreach ($subMTimes as $subName => $subMTime) {
                    $map[$key . '/' . $subName] = $subMTime;
                }
                continue;
            }

            $map[$key] = $value->mtime;
        }
        return $map;
    }
}