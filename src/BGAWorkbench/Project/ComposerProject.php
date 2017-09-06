<?php

namespace BGAWorkbench\Project;

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
    public function getAllFiles(): array
    {
        return array_merge(parent::getAllFiles(), $this->getVendorFiles());
    }

    /**
     * @return SplFileInfo[]
     */
    private function getVendorFiles(): array
    {
        if ($this->vendorFiles === null) {
            $this->vendorFiles = $this->createVendorFiles();
        }
        return $this->vendorFiles;
    }

    /**
     * @return SplFileInfo[]
     */
    private function createVendorFiles(): array
    {
        $buildDir = $this->buildProdVendors();

        $finder = Finder::create()
            ->in($buildDir->getPathname())
            ->files()
            ->notName('composer.lock')
            ->notName('composer.json')
            ->notPath('/tests/')
            ->notPath('/bin/');
        return array_values(iterator_to_array($finder));
    }

    /**
     * @todo Must be some third party file system helpers to do this
     * @param string $src
     * @param string $dst
     */
    private function copyDir($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * @return \SplFileInfo
     */
    public function buildProdVendors()
    {
        $buildDir = new \SplFileInfo($this->getBuildDirectory() . DIRECTORY_SEPARATOR . 'prod-vendors');
        if (!file_exists($buildDir->getPathname() . '/src')) {
            mkdir($buildDir->getPathname() . '/src', 0777, true);
        }
        $this->copyDir('src/TheBattleForHill218', $buildDir->getPathname() . '/src/TheBattleForHill218');
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

        return $buildDir;
    }
}
