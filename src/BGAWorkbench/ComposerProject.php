<?php

namespace BGAWorkbench;

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
    public function getAllFiles()
    {
        return parent::getAllFiles()->concat($this->getVendorFiles());
    }

    /**
     * @return ImmArray
     */
    private function getVendorFiles()
    {
        if ($this->vendorFiles === null) {
            $this->vendorFiles = $this->createVendorFiles();
        }
        return $this->vendorFiles;
    }

    /**
     * @return ImmArray
     */
    private function createVendorFiles()
    {
        $tempDir = new \SplFileInfo(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getName() . '-vendors');
        $fileSystem = new Filesystem();
        // TODO: Persist vendor/autoload.php and vendor/composer
        $fileSystem->remove($tempDir->getPathname());
        $fileSystem->mkdir($tempDir->getPathname());
        foreach (array('composer.json', 'composer.lock') as $composerFileName) {
            $fileSystem->copy(
                $this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $composerFileName,
                $tempDir->getPathname() . DIRECTORY_SEPARATOR . $composerFileName
            );
        }

        $builder = new ProcessBuilder(array(
            'composer',
            'install',
            '--no-dev',
            '-o',
            '-d',
            $tempDir->getPathname()
        ));
        $process = $builder->getProcess();
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $finder = Finder::create()
            ->in($tempDir->getPathname())
            ->files()
            ->notName('composer.lock')
            ->notName('composer.json')
            ->notPath('/tests/')
            ->notPath('/bin/');
        return ImmArray::fromArray(array_values(iterator_to_array($finder)));
    }
}
