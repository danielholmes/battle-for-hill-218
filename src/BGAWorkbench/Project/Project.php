<?php

namespace BGAWorkbench\Project;

use BGAWorkbench\Utils;
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
    public function __construct(\SplFileInfo $directory, string $name, ImmArray $extraSrcPaths)
    {
        $this->directory = $directory;
        $this->name = $name;
        $this->extraSrcPaths = $extraSrcPaths;
    }

    /**
     * @return \SplFileInfo
     */
    public function getDirectory() : \SplFileInfo
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getGameProjectFileRelativePathname() : string
    {
        return "{$this->name}.game.php";
    }

    /**
     * @return string
     */
    public function getActionProjectFileRelativePathname() : string
    {
        return "{$this->name}.action.php";
    }

    /**
     * @return string
     */
    public function getGameinfosProjectFileRelativePathname() : string
    {
        return "gameinfos.inc.php";
    }

    /**
     * @return string
     */
    private function getDbModelSqlRelativePathname() : string
    {
        return "dbmodel.sql";
    }

    /**
     * @return SplFileInfo
     */
    public function getDbModelSqlFile() : SplFileInfo
    {
        return $this->getProjectFile($this->getDbModelSqlRelativePathname());
    }

    /**
     * @return string
     */
    private function getStatesFileName() : string
    {
        return 'states.inc.php';
    }

    /**
     * @return ImmArray
     */
    public function getRequiredFiles() : ImmArray
    {
        return ImmArray::fromArray([
            $this->getActionProjectFileRelativePathname(),
            $this->getGameProjectFileRelativePathname(),
            "{$this->name}.view.php",
            "{$this->name}.css",
            "{$this->name}.js",
            "{$this->name}_{$this->name}.tpl",
            $this->getDbModelSqlRelativePathname(),
            $this->getGameinfosProjectFileRelativePathname(),
            "gameoptions.inc.php",
            "material.inc.php",
            $this->getStatesFileName(),
            "stats.inc.php",
            "version.php",
            "img" . DIRECTORY_SEPARATOR . "game_box.png",
            "img" . DIRECTORY_SEPARATOR . "game_box75.png",
            "img" . DIRECTORY_SEPARATOR . "game_box180.png",
            "img" . DIRECTORY_SEPARATOR . "game_icon.png",
            "img" . DIRECTORY_SEPARATOR . "publisher.png"
        ])->map(function ($name) {
            return $this->getProjectFile($name);
        });
    }

    /**
     * @param SplFileInfo $file
     * @param ImmArray $exclude
     * @return ImmArray
     */
    private function getPathFiles(SplFileInfo $file, ImmArray $exclude) : ImmArray
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
                function (SplFileInfo $file) {
                    return $this->absoluteToProjectRelativeFile($file);
                }
            );
    }

    /**
     * @return ImmArray
     */
    public function getAllFiles() : ImmArray
    {
        return $this->getBaseProjectFiles();
    }

    /**
     * @return ImmArray
     */
    private function getBaseProjectFiles() : ImmArray
    {
        return $this->getDevelopmentLocations()
            ->reduce(
                function (ImmArray $current, SplFileInfo $file) {
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
    public function getDevelopmentPhpFiles() : ImmArray
    {
        return $this->getBaseProjectFiles()
            ->filter(function (SplFileInfo $file) {
                return $file->getExtension() === 'php';
            });
    }

    /**
     * @return ImmArray
     */
    public function getDevelopmentLocations() : ImmArray
    {
        $required = $this->getRequiredFiles();
        return $required
            ->concat(
                $this->extraSrcPaths
                    ->concat(ImmArray::fromArray(['img']))
                    ->map(function ($path) {
                        return $this->getProjectFile($path);
                    })
            );
    }

    /**
     * @return array
     */
    public function getStates() : array
    {
        $variableName = 'machinestates';
        return $this->getFileVariableValue($this->getStatesFileName(), $variableName)
            ->getOrThrow(new \RuntimeException("Couldn't find states"));
    }

    /**
     * @param string $fileName
     * @param string|callable $predicate
     * @return Option
     */
    public function getFileVariableValue(string $fileName, $predicate) : Option
    {
        return Utils::getVariableValueFromFile($this->getProjectFile($fileName), $predicate);
    }

    /**
     * @param string $relativePath
     * @return SplFileInfo
     */
    protected function getProjectFile($relativePath) : SplFileInfo
    {
        return $this->createProjectFile($this->directory->getPathname() . DIRECTORY_SEPARATOR . $relativePath);
    }

    /**
     * @param \SplFileInfo $file
     * @return SplFileInfo
     */
    public function absoluteToProjectRelativeFile(\SplFileInfo $file) : SplFileInfo
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
    private function createProjectFile($pathname) : SplFileInfo
    {
        $relativePathname = str_replace_first($this->directory->getPathname() . DIRECTORY_SEPARATOR, '', $pathname);
        return new SplFileInfo($pathname, dirname($relativePathname), $relativePathname);
    }

    /**
     * @return array
     */
    public function getGameInfos() : array
    {
        return Utils::getVariableValueFromFile(
            $this->getProjectFile($this->getGameinfosProjectFileRelativePathname()),
            'gameinfos'
        )->get();
    }

    /**
     * @return \Table
     */
    public function createGameTableInstance() : \Table
    {
        return $this->createInstanceFromClassInFile($this->getGameProjectFileRelativePathname(), 'Table');
    }

    /**
     * @return \APP_GameAction
     */
    public function createActionInstance() : \APP_GameAction
    {
        return $this->createInstanceFromClassInFile($this->getActionProjectFileRelativePathname(), 'APP_GameAction');
    }

    /**
     * @param string $relativePathname
     * @param string $class
     * @return mixed
     */
    private function createInstanceFromClassInFile(string $relativePathname, string $class)
    {
        $gameFilepath = $this->getProjectFile($relativePathname)->getPathname();
        require_once($gameFilepath);
        $tableClasses = ImmArray::fromArray(array_keys(AnnotationsParser::parsePhp(file_get_contents($gameFilepath))))
            ->map(function ($className) {
                return new \ReflectionClass($className);
            })
            ->filter(function ($refClass) use ($class) {
                return $refClass->getParentClass()->getName() === $class;
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
