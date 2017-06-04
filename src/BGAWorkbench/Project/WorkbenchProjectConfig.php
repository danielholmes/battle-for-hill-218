<?php

namespace BGAWorkbench\Project;

use BGAWorkbench\Utils;
use PhpOption\Option;
use Qaribou\Collection\ImmArray;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
    private $testDbNamePrefix;

    /**
     * @var string
     */
    private $testDbUsername;

    /**
     * @var string
     */
    private $testDbPassword;

    /**
     * @var string
     */
    private $linterPhpBin;

    /**
     * @var Option
     */
    private $sftpConfig;

    /**
     * @param \SplFileInfo $directory
     * @param boolean $useComposer
     * @param ImmArray $extraSrcPaths
     * @param string $testDbNamePrefix
     * @param string $testDbUsername
     * @param string $testDbPassword
     * @param string $linterPhpBin
     * @param Option $sftpConfig
     */
    public function __construct(
        \SplFileInfo $directory,
        $useComposer,
        ImmArray $extraSrcPaths,
        $testDbNamePrefix,
        $testDbUsername,
        $testDbPassword,
        $linterPhpBin,
        Option $sftpConfig
    ) {
    
        $this->directory = $directory;
        $this->useComposer = $useComposer;
        $this->extraSrcPaths = $extraSrcPaths;
        $this->testDbNamePrefix = $testDbNamePrefix;
        $this->testDbUsername = $testDbUsername;
        $this->testDbPassword = $testDbPassword;
        $this->linterPhpBin = $linterPhpBin;
        $this->sftpConfig = $sftpConfig;
    }

    /**
     * @return string
     */
    public function getTestDbNamePrefix()
    {
        return $this->testDbNamePrefix;
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
     * @return string
     */
    public function getLinterPhpBin()
    {
        return $this->linterPhpBin;
    }

    /**
     * @return Option
     */
    public function getDeployConfig()
    {
        return $this->sftpConfig;
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
            function ($name) use ($GAME_VERSION_PREFIX) {
                return strpos($name, $GAME_VERSION_PREFIX) === 0;
            }
        )->getOrThrow(
            new \InvalidArgumentException(
                "File {$versionFile->getPathname()} missing version variable {$GAME_VERSION_PREFIX}_%%project_name%%"
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
        $filepath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'bgaproject.yml';
        $rawContent = @file_get_contents($filepath);
        if ($rawContent === false) {
            throw new \InvalidArgumentException("Couldn't read project config {$filepath}");
        }

        try {
            $rawConfig = Yaml::parse($rawContent);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException("Invalid YAML in file {$filepath}", 0, $e);
        }

        $processor = new Processor();
        $processed = $processor->processConfiguration(new ConfigFileConfiguration(), [$rawConfig]);
        return new WorkbenchProjectConfig(
            $directory,
            $processed['useComposer'],
            ImmArray::fromArray($processed['extraSrc']),
            $processed['testDb']['namePrefix'],
            $processed['testDb']['user'],
            $processed['testDb']['pass'],
            $processed['linterPhpBin'],
            Option::fromValue($processed['sftp'])->map(function (array $raw) {
                return new DeployConfig($raw['host'], $raw['user'], $raw['pass']);
            })
        );
    }
}
