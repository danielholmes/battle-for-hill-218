<?php

namespace GBAWorkbench;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Project
{
    /**
     * @var \SplFileInfo
     */
    private $directory;

    /**
     * @var string
     */
    private $name;

    /**
     * @param \SplFileInfo $directory
     * @param string $name
     */
    private function __construct(\SplFileInfo $directory, $name) {
        $this->directory = $directory;
        $this->name = $name;
    }

    /**
     * @return \SplFileInfo
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return SplFileInfo[]
     */
    public function getAllFiles()
    {
        $finder = new Finder();
        $finder->in($this->directory->getPathname())
            ->files()
            ->name('*.php')
            ->name('*.css')
            ->name('*.js')
            ->name('*.png')
            ->name('*.jpg')
            ->name('*.sql')
            ->name('*.tpl')
            ->exclude('vendor')
            ->exclude('src/GBAWorkbench')
            ->exclude('bin');
        return array_values(iterator_to_array($finder));
    }

    /**
     * @param string $path
     * @return SplFileInfo
     */
    public function getFile($path)
    {
        if (strpos($path, $this->directory->getPathname()) !== 0) {
            throw new \RuntimeException("File {$path} not within project");
        }
        $relativePathname = str_replace_first($this->directory->getPathname(), '', $path);
        return new SplFileInfo($path, dirname($relativePathname), $relativePathname);
    }

    /**
     * @param \SplFileInfo $directory
     * @return Project
     */
    public static function loadFrom(\SplFileInfo $directory) {
        $versionFilepath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'version.php';
        if (!is_readable($versionFilepath)) {
            throw new \InvalidArgumentException("Couldn't find version.php in directory {$directory->getPathname()}");
        }

        $GAME_VERSION_PREFIX = 'game_version_';
        include($versionFilepath);
        $projectName = array_reduce(
            array_keys(get_defined_vars()),
            function($current, $var) use ($GAME_VERSION_PREFIX) {
                if (strpos($var, $GAME_VERSION_PREFIX) === 0) {
                    return substr($var, strlen($GAME_VERSION_PREFIX));
                }
                return $current;
            },
        null
        );
        if ($projectName === null) {
            throw new \InvalidArgumentException(
                "File {$versionFilepath} doesn't have expected version variable ${$GAME_VERSION_PREFIX}_%%project_name%%"
            );
        }

        return new Project($directory, $projectName);
    }
}