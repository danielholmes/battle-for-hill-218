<?php

namespace BGAWorkbench;

use Nette\Reflection\AnnotationsParser;
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
     * @return string
     */
    public function getGameProjectFileRelativePathname()
    {
        return "{$this->name}.game.php";
    }

    /**
     * @return string
     */
    public function getGameinfosProjectFileRelativePathname()
    {
        return "gameinfos.inc.php";
    }

    /**
     * @return string
     */
    private function getDbModelSqlRelativePathname()
    {
        return "dbmodel.sql";
    }

    /**
     * @return SplFileInfo
     */
    public function getDbModelSqlFile()
    {
        return $this->getProjectFile($this->getDbModelSqlRelativePathname());
    }

    /**
     * @return ImmArray
     */
    public function getRequiredFiles()
    {
        return ImmArray::fromArray([
            "{$this->name}.action.php",
            $this->getGameProjectFileRelativePathname(),
            "{$this->name}.view.php",
            "{$this->name}.css",
            "{$this->name}.js",
            "{$this->name}_{$this->name}.tpl",
            $this->getDbModelSqlRelativePathname(),
            $this->getGameinfosProjectFileRelativePathname(),
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
        ])->map(function($name) { return $this->getProjectFile($name); });
    }

    /**
     * @param SplFileInfo $file
     * @param ImmArray $exclude
     * @return ImmArray
     */
    private function getPathFiles(SplFileInfo $file, ImmArray $exclude)
    {
        $finder = Finder::create()
            ->in($file->getPathname())
            ->files();
        foreach ($exclude as $excludeFile) {
            if ($excludeFile->getRelativePath() === $file->getRelativePathname()) {
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
        return $this->getDevelopmentLocations()
            ->reduce(
                function(ImmArray $current, SplFileInfo $file) {
                    if ($file->isFile()) {
                        return $current->concat(ImmArray::fromArray([$file]));
                    }

                    return $current->concat($this->getPathFiles($file, $this->getRequiredFiles()));
                },
                ImmArray::fromArray([])
            );
    }

    /**
     * @return ImmArray
     */
    public function getDevelopmentLocations()
    {
        $required = $this->getRequiredFiles();
        return $required
            ->concat(
                $this->extraSrcPaths
                    ->concat(ImmArray::fromArray(['img']))
                    ->map(function($path) { return $this->getProjectFile($path); })
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

    /**
     * @return array
     */
    public function getGameInfos()
    {
        return Utils::getVariableValueFromFile(
            $this->getProjectFile($this->getGameinfosProjectFileRelativePathname()),
            'gameinfos'
        )->get();
    }

    /**
     * @return \Table
     */
    public function createTableInstance()
    {
        $gameFilepath = $this->getProjectFile($this->getGameProjectFileRelativePathname())->getPathname();
        require_once($gameFilepath);
        $tableClasses = ImmArray::fromArray(array_keys(AnnotationsParser::parsePhp(file_get_contents($gameFilepath))))
            ->map(function($className) { return new \ReflectionClass($className); })
            ->filter(function($refClass) {
                return $refClass->getParentClass()->getName() === 'Table';
            });
        $numTableClasses = $tableClasses->count();
        if ($numTableClasses !== 1) {
            throw new \RuntimeException(
                "Expected exactly one Table classes in game file {$gameFilepath}, found exactly {$numTableClasses}"
            );
        }
        $tableClass = $tableClasses->current();
        return $tableClass->newInstance();
    }
}