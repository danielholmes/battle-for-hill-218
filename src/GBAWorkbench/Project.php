<?php

namespace GBAWorkbench;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Project
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $directory
     * @param string $name
     */
    private function __construct($directory, $name) {
        $this->directory = $directory;
        $this->name = $name;
    }

    /**
     * @return string
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
        $finder->in($this->directory)
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
     * @param $directory
     * @return Project
     */
    public static function loadFrom($directory) {
        $versionFilepath = $directory . DIRECTORY_SEPARATOR . 'version.php';
        if (!is_readable($versionFilepath)) {
            throw new \InvalidArgumentException(sprintf('Couldn\'t find version.php in directory %s', $directory));
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
            throw new \InvalidArgumentException(sprintf(
                'File %s doesn\'t have expected version variable $%s_%%project_name%%',
                $versionFilepath,
                $GAME_VERSION_PREFIX
            ));
        }

        return new Project($directory, $projectName);
    }
}