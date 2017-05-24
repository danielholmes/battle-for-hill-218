<?php


namespace GBAWorkbench;

use Symfony\Component\Config\Definition\Processor;

class ProjectWorkbenchConfig
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
     * @param Project $project
     * @return ProjectWorkbenchConfig
     */
    public static function loadFrom(Project $project)
    {
        $filepath = $project->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . 'gbaproject.json';
        $rawContent = @file_get_contents($filepath);
        if ($rawContent === false) {
            throw new \InvalidArgumentException("Couldn't read project config {$filepath}");
        }

        $rawConfig = @json_decode($rawContent, true);
        $processor = new Processor();
        $processed = $processor->processConfiguration(new ConfigFileConfiguration(), array($rawConfig));
        return new ProjectWorkbenchConfig(
            $processed['sftp']['host'],
            $processed['sftp']['user'],
            $processed['sftp']['pass']
        );
    }
}