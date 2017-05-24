<?php

namespace BGAWorkbench;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

class ComposerProject extends Project
{
    /**
     * @inheritdoc
     */
    public function getAllFiles()
    {
        $vendorFiles = $this->createVendorFiles();
        return array_merge(parent::getAllFiles(), $vendorFiles);
    }

    /**
     * @return array
     */
    private function createVendorFiles()
    {
        $distDir = new \SplFileInfo($this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . 'dist');
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($distDir->getPathname())) {
            $fileSystem->mkdir($distDir->getPathname());
        }
        foreach (array('composer.json', 'composer.lock') as $composerFileName) {
            $fileSystem->copy(
                $this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $composerFileName,
                $distDir->getPathname() . DIRECTORY_SEPARATOR . $composerFileName
            );
        }

        $builder = new ProcessBuilder(array(
            'php',
            $this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . 'composer.phar',
            'install',
            '--no-dev',
            '-o',
            '-d',
            $distDir->getPathname()
        ));
        $process = $builder->getProcess();
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $finder = new Finder();
        $finder->in($distDir->getPathname())
            ->files()
            ->notName('composer.lock')
            ->notName('composer.json')
            ->notPath('bin');
        return array_values(iterator_to_array($finder));
    }
}
