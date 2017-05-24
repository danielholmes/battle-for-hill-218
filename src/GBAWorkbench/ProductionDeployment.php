<?php


namespace GBAWorkbench;


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
     * @param array $files
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
            $this->deployFile($file, $file->getRelativePathname());
        }
        return count($newerFiles);
    }

    /**
     * @param SplFileInfo $file
     * @param string $remoteName
     */
    public function deployFile(SplFileInfo $file, $remoteName)
    {
        $remotePathname = "{$this->directory}/{$remoteName}";
        if (!$this->sftp->put($remotePathname, $file->getPathname(), SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Error transferring {$file->getPathname()} to {$remotePathname}");
        }
    }

    /**
     * @return array
     */
    private function getMTimesByFilepath()
    {
        $rawList = $this->sftp->rawlist($this->directory, true);
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
}