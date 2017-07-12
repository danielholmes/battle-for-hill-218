<?php

namespace BGAWorkbench\Project;

use Qaribou\Collection\ImmArray;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

class ComposerProject extends Project
{
    /**
     * @var SplFileInfo
     */
    private $vendorFiles;

    /**
     * @inheritdoc
     */
    public function getAllFiles() : ImmArray
    {
        return parent::getAllFiles()->concat($this->getVendorFiles());
    }

    /**
     * @return ImmArray
     */
    private function getVendorFiles() : ImmArray
    {
        if ($this->vendorFiles === null) {
            $this->vendorFiles = $this->createVendorFiles();
        }
        return $this->vendorFiles;
    }

    /**
     * @return ImmArray
     */
    private function createVendorFiles() : ImmArray
    {
        $buildDir = new \SplFileInfo($this->getProjectFile('build') . DIRECTORY_SEPARATOR . 'prod-vendors');
        $buildVendorConfig = new \SplFileInfo($buildDir->getPathname() . DIRECTORY_SEPARATOR . 'composer.json');
        $projectConfig = $this->getProjectFile('composer.json');
        if (!$buildVendorConfig->isFile() || $buildVendorConfig->getMTime() < $projectConfig->getMTime()) {
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($buildDir->getPathname());
            foreach (['composer.json', 'composer.lock'] as $composerFileName) {
                $fileSystem->copy(
                    $this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $composerFileName,
                    $buildDir->getPathname() . DIRECTORY_SEPARATOR . $composerFileName
                );
            }

            $process = ProcessBuilder::create([
                'composer',
                'install',
                '--no-dev',
                '-o',
                '-d',
                $buildDir->getPathname()
            ])->getProcess();
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }

        $finder = Finder::create()
            ->in($buildDir->getPathname())
            ->files()
            ->notName('composer.lock')
            ->notName('composer.json')
            ->notPath('/tests/')
            ->notPath('/bin/');
        return ImmArray::fromArray(array_values(iterator_to_array($finder)));
    }
}
