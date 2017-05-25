<?php


namespace BGAWorkbench;

use phpseclib\Net\SFTP;
use Qaribou\Collection\ImmArray;
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
     * @var ImmArray
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
     * @param ImmArray $files
     * @param callable $callable
     * @return int
     */
    public function deployChangedFiles(ImmArray $files, $callable)
    {
        $remoteMTimes = $this->getMTimesByFilepath();
        $newerFiles = $files->filter(
            function(SplFileInfo $file) use ($remoteMTimes) {
                return !isset($remoteMTimes[$file->getRelativePathname()]) ||
                    $remoteMTimes[$file->getRelativePathname()] < $file->getMTime();
            }
        );
        $total = $newerFiles->count();
        $newerFiles->walk(function(SplFileInfo $file, $i) use ($callable, $total) {
            $num = $i + 1;
            call_user_func($callable, $num, $total, $file);
            $this->deployFile($file);
        });
        return $total;
    }

    /**
     * @return ImmArray
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
    public function remove(SplFileInfo $file)
    {
        $remoteName = $file->getRelativePathname();
        if (!$this->sftp->delete($remoteName)) {
            throw new \RuntimeException("Error deleting {$remoteName}");
        }
    }

    /**
     * @param SplFileInfo $file
     */
    public function deployFile(SplFileInfo $file)
    {
        $remoteName = $file->getRelativePathname();
        $remoteDirectories = $this->getRemoteDirectories();
        $remoteDirpath = dirname($remoteName);
        if ($remoteDirpath !== '.' && !in_array($remoteDirpath, $remoteDirectories->toArray(), true)) {
            $fullRemoteDirpath = "{$this->directory}/{$remoteDirpath}";
            if (!$this->sftp->mkdir($fullRemoteDirpath, -1, true)) {
                throw new \RuntimeException("Error creating directory {$fullRemoteDirpath}");
            }
            $this->remoteDirectories = $this->remoteDirectories->concat($this->pathToAllSubPaths($remoteDirpath));
        }

        $fullRemotePathname = "{$this->directory}/{$remoteName}";
        if (!$this->sftp->put($fullRemotePathname, $file->getPathname(), SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Error transferring {$file->getPathname()} to {$remoteName}");
        }
    }

    /**
     * @param string $remoteDirpath
     * @return ImmArray
     */
    private function pathToAllSubPaths($remoteDirpath)
    {
        $parts = explode('/', $remoteDirpath);
        return ImmArray::fromArray(range(1, count($parts)))
            ->map(function($i) use ($parts) { return join('/', array_slice($parts, 0, $i)); });
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
     * @return ImmArray
     */
    private function rawListToDirectories(array $rawRemoteList)
    {
        $directories = ImmArray::fromArray(array());
        foreach ($rawRemoteList as $key => $value) {
            if ($key === '.' || $key === '..') {
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            $subDirectories = $this->rawListToDirectories($value);
            $directories = $directories->concat(ImmArray::fromArray(array($key)))
                ->concat($subDirectories->map(function($subDir) use ($key) { return $key . '/' . $subDir; }));
        }
        return $directories;
    }
}