<?php

namespace BGAWorkbench\Project;

use Symfony\Component\Config\Definition\Processor;

class WorkbenchDeployConfig
{
    /**
     * @var string
     */
    private $sftpHost;

    /**
     * @var string
     */
    private $sftpUsername;

    /**
     * @var string
     */
    private $sftpPassword;

    /**
     * @param string $sftpHost
     * @param string $sftpUsername
     * @param string $sftpPassword
     */
    public function __construct($sftpHost, $sftpUsername, $sftpPassword)
    {
        $this->sftpHost = $sftpHost;
        $this->sftpUsername = $sftpUsername;
        $this->sftpPassword = $sftpPassword;
    }

    /**
     * @return string
     */
    public function getSftpHost()
    {
        return $this->sftpHost;
    }

    /**
     * @return string
     */
    public function getSftpUsername()
    {
        return $this->sftpUsername;
    }

    /**
     * @return string
     */
    public function getSftpPassword()
    {
        return $this->sftpPassword;
    }

    /**
     * @return WorkbenchDeployConfig
     */
    public static function loadFromCwd()
    {
        return self::loadFrom(new \SplFileInfo(getcwd()));
    }

    /**
     * @param \SplFileInfo $directory
     * @return WorkbenchDeployConfig
     */
    public static function loadFrom(\SplFileInfo $directory)
    {
        $filepath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'bgadeploy.json';
        $rawContent = @file_get_contents($filepath);
        if ($rawContent === false) {
            throw new \InvalidArgumentException("Couldn't read deploy config {$filepath}");
        }

        $rawConfig = @json_decode($rawContent, true);
        $processor = new Processor();
        $processed = $processor->processConfiguration(new DeployConfiguration(), [$rawConfig]);
        return new WorkbenchDeployConfig(
            $processed['sftp']['host'],
            $processed['sftp']['user'],
            $processed['sftp']['pass']
        );
    }
}