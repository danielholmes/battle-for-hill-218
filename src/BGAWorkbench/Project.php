<?php

namespace BGAWorkbench;

use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\Finder\SplFileInfo;
use PhpOption\Option;

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
     * @var string[]
     */
    private $extraSrcPaths;

    /**
     * @param \SplFileInfo $directory
     * @param string $name
     * @param string[] $extraSrcPaths
     */
    public function __construct(\SplFileInfo $directory, $name, array $extraSrcPaths) {
        $this->directory = $directory;
        $this->name = $name;
        $this->extraSrcPaths = $extraSrcPaths;
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

    public function getDeveloperFiles()
    {

    }

    /**
     * @return SplFileInfo[]
     */
    public function getAllFiles()
    {
        $directory = $this->directory;
        return array_reduce(
            array(
                "{$this->name}.action.php",
                "{$this->name}.game.php",
                "{$this->name}.view.php",
                "{$this->name}.css",
                "{$this->name}.js",
                "{$this->name}_{$this->name}.tpl",
                "dbmodel.sql",
                "gameinfos.inc.php",
                "gameoptions.inc.php",
                "material.inc.php",
                "states.inc.php",
                "stats.inc.php",
                "version.php",
                "img"
            ),
            function(array $current, $name) use ($directory) {
                $path = $directory->getPathname() . DIRECTORY_SEPARATOR . $name;
                if (is_dir($path)) {
                    $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::CURRENT_AS_FILEINFO);
                    $files = array_map(
                        function(SplFileInfo $file) use ($name) {
                            $relativePath = $name;
                            $relativePathname = $name . DIRECTORY_SEPARATOR . $file->getRelativePathname();
                            return new SplFileInfo($file->getPathname(), $relativePath, $relativePathname);
                        },
                        array_values(
                            array_filter(
                                iterator_to_array($iterator),
                                function(SplFileInfo $file) {
                                    return !in_array($file->getRelativePathname(), array('.', '..'), true);
                                }
                            )
                        )
                    );
                    return array_merge($current, $files);
                }
                return array_merge($current, array(new SplFileInfo($path, '', $name)));
            },
            array()
        );

        /*$finder = new Finder();
        foreach ($this->extraSrcPaths as $path) {
            $fullPath = $this->directory->getPathname() . DIRECTORY_SEPARATOR . $path;
            if (is_file($fullPath)) {
                $finder = $finder->append(new \ArrayIterator(array($this->createProjectFile($fullPath))));
                continue;
            }
            $pathFinder = new Finder();
            $finder = $finder->append(
                $pathFinder->in($this->directory->getPathname())
                    ->files()
                    ->path($path)
            );
        }
        return array_values(iterator_to_array($finder));*/
    }

    /**
     * @param string $fileName
     * @param string|callable $predicate
     * @return Option
     */
    public function getFileVariableValue($fileName, $predicate)
    {
        return Utils::getVariableValueFromFile($this->getProjectFile($fileName), $predicate);
    }

    /**
     * @param string $relativePath
     * @return SplFileInfo
     */
    private function getProjectFile($relativePath)
    {
        return $this->createProjectFile($this->directory->getPathname() . DIRECTORY_SEPARATOR . $relativePath);
    }

    /**
     * @param \SplFileInfo $file
     * @return SplFileInfo
     */
    public function absoluteToProjectRelativeFile(\SplFileInfo $file)
    {
        if (strpos($file->getPathname(), $this->directory->getPathname()) !== 0) {
            throw new \RuntimeException("File {$file->getPathname()} not within project");
        }
        return $this->createProjectFile($file->getPathname());
    }

    /**
     * @param string $pathname
     * @return SplFileInfo
     */
    private function createProjectFile($pathname)
    {
        $relativePathname = str_replace_first($this->directory->getPathname() . DIRECTORY_SEPARATOR, '', $pathname);
        return new SplFileInfo($pathname, dirname($relativePathname), $relativePathname);
    }
}