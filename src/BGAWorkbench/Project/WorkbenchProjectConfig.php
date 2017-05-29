<?php

namespace BGAWorkbench\Project;

use BGAWorkbench\Utils;
use Qaribou\Collection\ImmArray;
use Symfony\Component\Config\Definition\Processor;

class WorkbenchProjectConfig
{
    /**
     * @var \SplFileInfo
     */
    private $directory;

    /**
     * @var boolean
     */
    private $useComposer;

    /**
     * @var ImmArray
     */
    private $extraSrcPaths;

    /**
     * @var string
     */
    private $testDbUsername;

    /**
     * @var string
     */
    private $testDbPassword;

    /**
     * @param \SplFileInfo $directory
     * @param boolean $useComposer
     * @param ImmArray $extraSrcPaths
     * @param string $testDbUsername
     * @param string $testDbPassword
     */
    public function __construct(
        \SplFileInfo $directory,
        $useComposer,
        ImmArray $extraSrcPaths,
        $testDbUsername,
        $testDbPassword
    )
    {
        $this->directory = $directory;
        $this->useComposer = $useComposer;
        $this->extraSrcPaths = $extraSrcPaths;
        $this->testDbUsername = $testDbUsername;
        $this->testDbPassword = $testDbPassword;
    }

    /**
     * @return string
     */
    public function getTestDbUsername()
    {
        return $this->testDbUsername;
    }

    /**
     * @return string
     */
    public function getTestDbPassword()
    {
        return $this->testDbPassword;
    }

    /**
     * @return Project
     */
    public function loadProject()
    {
        $versionFile = new \SplFileInfo($this->directory->getPathname() . DIRECTORY_SEPARATOR . 'version.php');

        $GAME_VERSION_PREFIX = 'game_version_';
        $variableName = Utils::getVariableNameFromFile(
            $versionFile,
            function($name) use ($GAME_VERSION_PREFIX) { return strpos($name, $GAME_VERSION_PREFIX) === 0; }
        )->getOrThrow(
            new \InvalidArgumentException(
                "File {$versionFile->getPathname()} doesn't have expected version variable {$GAME_VERSION_PREFIX}_%%project_name%%"
            )
        );
        $projectName = substr($variableName, strlen($GAME_VERSION_PREFIX));

        if ($this->useComposer) {
            return new ComposerProject($this->directory, $projectName, $this->extraSrcPaths);
        }
        return new Project($this->directory, $projectName, $this->extraSrcPaths);
    }

    /**
     * @return WorkbenchProjectConfig
     */
    public static function loadFromCwd()
    {
        return self::loadFrom(new \SplFileInfo(getcwd()));
    }

    /**
     * @param \SplFileInfo $directory
     * @return WorkbenchProjectConfig
     */
    public static function loadFrom(\SplFileInfo $directory)
    {
        $filepath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'bgaproject.json';
        $rawContent = @file_get_contents($filepath);
        if ($rawContent === false) {
            throw new \InvalidArgumentException("Couldn't read project config {$filepath}");
        }

        $rawConfig = @json_decode($rawContent, true);
        $processor = new Processor();
        $processed = $processor->processConfiguration(new ConfigFileConfiguration(), [$rawConfig]);
        return new WorkbenchProjectConfig(
            $directory,
            $processed['useComposer'],
            ImmArray::fromArray($processed['extraSrc']),
            $processed['testDb']['user'],
            $processed['testDb']['pass']
        );
    }
}