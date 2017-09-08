<?php

namespace BGAWorkbench\Builder;

use BGAWorkbench\Utils\FileUtils;
use BGAWorkbench\Utils\NameAccumulatorNodeVisitor;
use Composer\Autoload\ClassLoader;
use Illuminate\Filesystem\Filesystem;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;
use Functional as F;

class CompileComposerGame implements BuildInstruction
{
    /**
     * @var SplFileInfo
     */
    private $buildDir;

    /**
     * @var SplFileInfo
     */
    private $composerJsonFile;

    /**
     * @var SplFileInfo
     */
    private $composerLockFile;

    /**
     * @var SplFileInfo
     */
    private $gameFile;

    /**
     * @var SplFileInfo[]
     */
    private $extraSrcPaths;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @param Filesystem $fileSystem
     * @param SplFileInfo $buildDir
     * @param SplFileInfo $composerJsonFile
     * @param SplFileInfo $composerLockFile
     * @param SplFileInfo $gameFile
     * @param SplFileInfo[] $extraSrcPaths
     */
    public function __construct(
        Filesystem $fileSystem,
        SplFileInfo $buildDir,
        SplFileInfo $composerJsonFile,
        SplFileInfo $composerLockFile,
        SplFileInfo $gameFile,
        array $extraSrcPaths
    ) {
    
        $this->fileSystem = $fileSystem;
        $this->buildDir = $buildDir;
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;
        $this->gameFile = $gameFile;
        $this->extraSrcPaths = $extraSrcPaths;
    }

    /**
     * @inheritdoc
     */
    public function getInputPaths() : array
    {
        return array_merge(
            [
                $this->composerLockFile,
                $this->gameFile
            ],
            $this->extraSrcPaths
        );
    }

    /**
     * @inheritdoc
     */
    public function runWithChanged(\SplFileInfo $distDir, array $changedFiles) : array
    {
        return $this->run($distDir);
    }

    /**
     * @inheritdoc
     */
    public function run(\SplFileInfo $distDir) : array
    {
        // TODO: Only build Prod vendors if .lock changed
        $workingDir = $this->buildProdVendors();

        // TODO: Only run if any file newer than output
        $outputFile = new \SplFileInfo(
            $distDir->getPathname() . DIRECTORY_SEPARATOR . $this->gameFile->getRelativePathname()
        );
        $files = $this->createDependenciesFileList($workingDir);
        $sourceModifiedTime = F\reduce_left(
            $files,
            function (\SplFileInfo $file, $i, array $all, $current) {
                echo $file . "\n";
                return max($file->getMTime(), $current);
            },
            -1
        );

        echo 'Source modified ' . date('Y-m-d H:i:s', $sourceModifiedTime) . ' vs ' .
            date('Y-m-d h:i:s', $this->fileSystem->lastModified($outputFile->getPathname())) . "\n";
        if ($this->fileSystem->exists($outputFile) &&
            $sourceModifiedTime <= $this->fileSystem->lastModified($outputFile->getPathname())) {
            die('no comp');
            return [];
        }
        die('comp');

        $configFilepath = $this->buildDir->getPathname() . DIRECTORY_SEPARATOR . 'compiler-config.php';
        $filePaths = F\map(
            $files,
            function (\SplFileInfo $file) {
                return $file->getPathname();
            }
        );
        $this->fileSystem->put($configFilepath, '<?php return ' . var_export($filePaths, true) . ';');

        $process = ProcessBuilder::create([
            'classpreloader.php',
            'compile',
            '--config=' . $configFilepath,
            '--output=' . $outputFile->getPathname(),
            '--strip_comments=1'
        ])->getProcess();
        $result = $process->run();
        if ($result !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return [FileUtils::createRelativeFileFromExisting($distDir, $outputFile)];
    }

    /**
     * @param \SplFileInfo $workingDir
     * @return \SplFileInfo[]
     */
    private function createDependenciesFileList(\SplFileInfo $workingDir) : array
    {
        $loader = require($workingDir->getPathname() . '/vendor/autoload.php');

        if (!defined('APP_GAMEMODULE_PATH')) {
            define('APP_GAMEMODULE_PATH', __DIR__ . '/../Stubs/');
        }
        require_once(APP_GAMEMODULE_PATH . 'framework.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_Object.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_DbObject.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_Action.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_GameAction.inc.php');

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $autoloadFiles = F\map(
            array_values(require($workingDir->getPathname() . '/vendor/composer/autoload_files.php')),
            function ($path) {
                return new \SplFileInfo($path);
            }
        );
        list($before, $after) = $this->getDependencyFiles(
            $parser,
            $loader,
            $this->gameFile,
            [$this->gameFile],
            [$this->gameFile]
        );
        return array_values(
            F\unique(
                array_merge($autoloadFiles, $before, [$this->gameFile], $after),
                function ($path) {
                    return strtolower(realpath($path));
                }
            )
        );
    }

    /**
     * @param Parser $parser
     * @param ClassLoader $loader
     * @param \SplFileInfo $file
     * @param \SplFileInfo[] $lineage
     * @param \SplFileInfo[] $alreadySeen
     * @return array
     */
    private function getDependencyFiles(
        Parser $parser,
        ClassLoader $loader,
        \SplFileInfo $file,
        array $lineage,
        array $alreadySeen
    ) {
        $parsed = $parser->parse($this->fileSystem->get($file->getPathname()));

        $visitor = new NameAccumulatorNodeVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($parsed);

        $uniqueNames = F\unique($visitor->names, function (Name $name) {
            return $name->toString();
        });
        $allSubFiles = F\unique(
            F\map(
                F\filter(
                    F\map(
                        $uniqueNames,
                        function (Name $name) use ($loader) {
                            return $loader->findFile($name->toString());
                        }
                    ),
                    function ($path) {
                        return $path;
                    }
                ),
                function ($path) {
                    return new \SplFileInfo($path);
                }
            ),
            null,
            false
        );
        $newSubFiles = F\filter(
            $allSubFiles,
            function (\SplFileInfo $file) use ($alreadySeen) {
                return !in_array($file, $alreadySeen, false);
            }
        );

        $current = [];
        foreach ($newSubFiles as $subFile) {
            list($newBefore, $newAfter) = $this->getDependencyFiles(
                $parser,
                $loader,
                $subFile,
                array_merge($lineage, [$subFile]),
                array_merge($alreadySeen, $current, [$subFile])
            );
            $current = array_merge($current, $newBefore, [$subFile], $newAfter);
        }

        $sharedLineageAndDeps = array_intersect($allSubFiles, $lineage);
        if (empty($sharedLineageAndDeps)) {
            return [$current, []];
        }

        return [[], $current];
    }

    /**
     * @todo Must be some third party file system helpers to do this
     * @param string $src
     * @param string $dst
     */
    private function copyDir($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * @return \SplFileInfo
     */
    public function buildProdVendors()
    {
        $buildDir = new \SplFileInfo($this->buildDir->getPathname() . DIRECTORY_SEPARATOR . 'prod-vendors');
        if (!file_exists($buildDir->getPathname() . '/src')) {
            mkdir($buildDir->getPathname() . '/src', 0777, true);
        }
        $this->copyDir('src/TheBattleForHill218', $buildDir->getPathname() . '/src/TheBattleForHill218');
        $buildVendorConfig = new \SplFileInfo($buildDir->getPathname() . DIRECTORY_SEPARATOR . 'composer.json');
        if (!$this->fileSystem->exists($buildVendorConfig->getPathname()) ||
            $buildVendorConfig->getMTime() < $this->composerJsonFile->getMTime()) {
            $this->fileSystem->makeDirectory($buildDir->getPathname(), 0755, true, true);
            foreach ([$this->composerJsonFile, $this->composerLockFile] as $composerFile) {
                $this->fileSystem->copy(
                    $composerFile->getPathname(),
                    $buildDir->getPathname() . DIRECTORY_SEPARATOR . $composerFile->getRelativePathname()
                );
            }

            $process = ProcessBuilder::create([
                'composer',
                'install',
                '--no-dev',
                '-o',
                '-d',
                $buildDir->getPathname()
            ])->getProcess();
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }

        return $buildDir;
    }
}
