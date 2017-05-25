<?php

namespace BGAWorkbench;

use Qaribou\Collection\ImmArray;
use Symfony\Component\Finder\Finder;
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
     * @var ImmArray
     */
    private $extraSrcPaths;

    /**
     * @param \SplFileInfo $directory
     * @param string $name
     * @param ImmArray $extraSrcPaths
     */
    public function __construct(\SplFileInfo $directory, $name, ImmArray $extraSrcPaths) {
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

    /**
     * @return ImmArray
     */
    public function getRequiredFiles()
    {
        return ImmArray::fromArray(array(
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
            "img" . DIRECTORY_SEPARATOR . "game_box.png",
            "img" . DIRECTORY_SEPARATOR . "game_box75.png",
            "img" . DIRECTORY_SEPARATOR . "game_box180.png",
            "img" . DIRECTORY_SEPARATOR . "game_icon.png",
            "img" . DIRECTORY_SEPARATOR . "publisher.png"
        ))->map(function($name) { return $this->getProjectFile($name); });
    }

    /**
     * @param string $path
     * @param ImmArray $exclude
     * @return ImmArray
     */
    private function getPathFiles($path, ImmArray $exclude = null)
    {
        if ($exclude === null) {
            $exclude = ImmArray::fromArray(array());
        }
        $finder = Finder::create()
            ->in($this->directory . DIRECTORY_SEPARATOR . $path)
            ->files();
        foreach ($exclude as $excludeFile) {
            if ($excludeFile->getRelativePath() === $path) {
                $finder = $finder->notName($excludeFile->getBasename());
            }
        }

        return ImmArray::fromArray(array_values(iterator_to_array($finder)))
            ->map(
                function(SplFileInfo $file) {
                    return $this->absoluteToProjectRelativeFile($file);
                }
            );
    }

    /**
     * @return ImmArray
     */
    public function getAllFiles()
    {
        $required = $this->getRequiredFiles();

        return $required
            ->concat(
                $this->extraSrcPaths
                    ->concat(ImmArray::fromArray(array('img')))
                    ->reduce(
                        function(ImmArray $current, $path) use ($required) {
                            return $current->concat($this->getPathFiles($path, $required));
                        },
                        ImmArray::fromArray(array())
                    )
            );
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