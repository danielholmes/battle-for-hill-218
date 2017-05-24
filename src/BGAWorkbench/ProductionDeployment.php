<?php


namespace BGAWorkbench;

use phpseclib\Net\SFTP;
use Symfony\Component\Finder\SplFileInfo;

class ProductionDeployment
{
    /**
     * @var SFTP
     */
    private $sftp;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string[]
     */
    private $remoteDirectories;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $directory
     */
    public function __construct($host, $username, $password, $directory)
    {
        $this->sftp = new SFTP($host);

        $this->username = $username;
        $this->password = $password;
        $this->directory = $directory;
    }

    /**
     *
     */
    public function connect()
    {
        if (!$this->sftp->login($this->username, $this->password)) {
            throw new \RuntimeException("Couldn't log in");
        }
    }

    /**
     * @param SplFileInfo[] $files
     * @param callable $callable
     * @return int
     */
    public function deployChangedFiles(array $files, $callable)
    {
        $remoteMTimes = $this->getMTimesByFilepath();
        $newerFiles = array_values(
            array_filter(
                $files,
                function(SplFileInfo $file) use ($remoteMTimes) {
                    return !isset($remoteMTimes[$file->getRelativePathname()]) ||
                        $remoteMTimes[$file->getRelativePathname()] < $file->getMTime();
                }
            )
        );
        foreach ($newerFiles as $i => $file) {
            $num = $i + 1;
            call_user_func($callable, $num, count($newerFiles), $file);
            $this->deployFile($file);
        }
        return count($newerFiles);
    }

    /**
     * @return string[]
     */
    private function getRemoteDirectories()
    {
        if ($this->remoteDirectories === null) {
            $rawList = $this->sftp->rawlist($this->directory, true);
            $this->remoteDirectories = $this->rawListToDirectories($rawList);
        }

        return $this->remoteDirectories;
    }

    /**
     * @param SplFileInfo $file
     */
    public function deployFile(SplFileInfo $file)
    {
        $remoteName = $file->getRelativePathname();
        $remoteDirectories = $this->getRemoteDirectories();
        $remoteDirpath = dirname($remoteName);
        if ($remoteDirpath !== '.' && !in_array($remoteDirpath, $remoteDirectories, true)) {
            $fullRemoteDirpath = "{$this->directory}/{$remoteDirpath}";
            if (!$this->sftp->mkdir($fullRemoteDirpath, -1, true)) {
                throw new \RuntimeException("Error creating directory {$fullRemoteDirpath}");
            }
            $this->remoteDirectories = array_merge($this->remoteDirectories, $this->pathToAllSubPaths($remoteDirpath));
        }

        $fullRemotePathname = "{$this->directory}/{$remoteName}";
        if (!$this->sftp->put($fullRemotePathname, $file->getPathname(), SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Error transferring {$file->getPathname()} to {$remoteName}");
        }
    }

    /**
     * @param string $remoteDirpath
     * @return string[]
     */
    private function pathToAllSubPaths($remoteDirpath)
    {
        $parts = explode('/', $remoteDirpath);
        return array_map(
            function($i) use ($parts) { return join('/', array_slice($parts, 0, $i)); },
            range(1, count($parts))
        );
    }

    /**
     * @return array
     */
    private function getMTimesByFilepath()
    {
        $rawList = $this->sftp->rawlist($this->directory, true);
        $this->remoteDirectories = $this->rawListToDirectories($rawList);
        return $this->rawListToMTimesByFilepath($rawList);
    }

    /**
     * @param array $rawRemoteList
     * @return array
     */
    private function rawListToMTimesByFilepath(array $rawRemoteList)
    {
        $map = array();
        foreach ($rawRemoteList as $key => $value) {
            if ($key === '.' || $key === '..') {
                continue;
            }

            if (is_array($value)) {
                $subMTimes = $this->rawListToMTimesByFilepath($value);
                foreach ($subMTimes as $subName => $subMTime) {
                    $map[$key . '/' . $subName] = $subMTime;
                }
                continue;
            }

            $map[$key] = $value->mtime;
        }
        return $map;
    }

    /**
     * @param array $rawRemoteList
     * @return string[]
     */
    private function rawListToDirectories(array $rawRemoteList)
    {
        $directories = array();
        foreach ($rawRemoteList as $key => $value) {
            if ($key === '.' || $key === '..') {
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            $subDirectories = $this->rawListToDirectories($value);
            $directories = array_merge(
                $directories,
                array($key),
                array_map(function($subDirectory) use ($key) { return $key . '/' . $subDirectory; }, $subDirectories)
            );
        }
        return $directories;
    }
}