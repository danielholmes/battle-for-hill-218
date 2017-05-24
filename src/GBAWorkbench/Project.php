<?php

namespace GBAWorkbench;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use PhpOption\Option;
use PhpOption\None;
use PhpOption\Some;

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
     * @param string $fileName
     * @param string|callable $predicate
     * @return Option
     */
    public function getFileVariableValue($fileName, $predicate)
    {
        return self::getVariableValueFromFile($this->getProjectFile($fileName), $predicate);
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
        $relativePathname = str_replace_first($this->directory->getPathname(), '', $pathname);
        return new SplFileInfo($pathname, dirname($relativePathname), $relativePathname);
    }

    /**
     * @param \SplFileInfo $directory
     * @return Project
     */
    public static function loadFrom(\SplFileInfo $directory) {
        $versionFile = new \SplFileInfo($directory->getPathname() . DIRECTORY_SEPARATOR . 'version.php');

        $GAME_VERSION_PREFIX = 'game_version_';
        $variableName = self::getVariableNameFromFile(
            $versionFile,
            function($name) use ($GAME_VERSION_PREFIX) { return strpos($name, $GAME_VERSION_PREFIX) === 0; }
        )->getOrThrow(
            new \InvalidArgumentException(
                "File {$versionFile->getPathname()} doesn't have expected version variable {$GAME_VERSION_PREFIX}_%%project_name%%"
            )
        );
        $projectName = substr($variableName, strlen($GAME_VERSION_PREFIX));

        return new Project($directory, $projectName);
    }

    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    private static function getVariableNameFromFile(\SplFileInfo $file, $namePredicate)
    {
        return self::getVariableFromFile($file, $namePredicate)
            ->map(function(array $variable) {
                list($name, $value) = $variable;
                return $name;
            });
    }

    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    private static function getVariableValueFromFile(\SplFileInfo $file, $namePredicate)
    {
        return self::getVariableFromFile($file, $namePredicate)
            ->map(function(array $variable) {
                list($name, $value) = $variable;
                return $value;
            });
    }

    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    private static function getVariableFromFile(\SplFileInfo $file, $namePredicate)
    {
        if (!$file->isReadable()) {
            throw new \InvalidArgumentException("Couldn't open file {$file->getPathname()}");
        }

        if (is_string($namePredicate)) {
            $stringNeedle = $namePredicate;
            $namePredicate = function($name) use ($stringNeedle) { return $name === $stringNeedle; };
        }

        include($file->getPathname());
        $definedVars = get_defined_vars();
        return array_reduce(
            array_keys($definedVars),
            function(Option $current, $name) use ($namePredicate, $definedVars) {
                if ($namePredicate($name)) {
                    return new Some(array($name, $definedVars[$name]));
                }
                return $current;
            },
            None::create()
        );
    }
}