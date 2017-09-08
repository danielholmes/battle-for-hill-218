<?php

namespace BGAWorkbench\Commands\BuildStrategy;

use BGAWorkbench\Project\Project;
use BGAWorkbench\Project\WorkbenchProjectConfig;
use BGAWorkbench\Utils\NameAccumulatorNodeVisitor;
use Composer\Autoload\ClassLoader;
use Illuminate\Filesystem\Filesystem;
use Functional as F;
use PhpOption\Option;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\ProcessBuilder;

class CompileBuildStrategy implements BuildStrategy
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->fileSystem = new Filesystem();
    }

    /**
     * @inheritdoc
     */
    public function run(OutputInterface $output, Option $changedFiles)
    {
        // TODO: Only run build instructions that match changed files
        // TODO: Generalise to both project types
        $gameOutputFile = new \SplFileInfo(
            $this->project->getDistDirectory()->getPathname() . '/' .
            $this->project->getGameProjectFileRelativePathname()
        );

        $changed1 = $this->copyFiles($changedFiles);
        $changed2 = $this->buildGameFile($gameOutputFile, $changedFiles);

        return F\unique(array_merge($changed1, $changed2), null, false);
    }

    /**
     * @param Option $changedFiles
     * @return SplFileInfo[]
     */
    private function copyFiles(Option $changedFiles)
    {
        $changedCopyFiles = $changedFiles
            ->map(function (array $files) {
                return array_intersect($this->project->getRequiredFiles(), $files);
            })
            ->getOrElse($this->project->getRequiredFiles());
        $nonGameFiles = array_values(
            F\filter(
                $changedCopyFiles,
                function (SplFileInfo $file) {
                    return $file != $this->project->getGameProjectFile();
                }
            )
        );
        F\each(
            $nonGameFiles,
            function (SplFileInfo $file) {
                $dest = new \SplFileInfo(
                    $this->project->getDistDirectory()->getPathname() . DIRECTORY_SEPARATOR .
                    $file->getRelativePathname()
                );
                if (!$this->fileSystem->exists($dest->getPath())) {
                    $this->fileSystem->makeDirectory($dest->getPath(), 0755, true);
                }
                $this->fileSystem->copy($file->getPathname(), $dest->getPathname());
            }
        );
        return $nonGameFiles;
    }

    /**
     * @param \SplFileInfo $gameOutputFile
     * @param Option $changedFiles
     * @return SplFileInfo[]
     */
    private function buildGameFile(\SplFileInfo $gameOutputFile, Option $changedFiles)
    {
        $workingDir = $this->project->buildProdVendors();
        $configFilepath = $this->project->getBuildDirectory()->getPathname() . '/compiler-config.php';
        $files = $this->createDependenciesFileList($workingDir);
        $this->fileSystem->put($configFilepath, '<?php return ' . var_export($files, true) . ';');

        $process = ProcessBuilder::create([
            'classpreloader.php',
            'compile',
            '--config=' . $configFilepath,
            '--output=' . $gameOutputFile->getPathname(),
            '--strip_comments=1'
        ])->getProcess();
        $result = $process->run();
        if ($result !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return [$this->project->absoluteToDistRelativeFile($gameOutputFile)];
    }

    /**
     * @param \SplFileInfo $workingDir
     * @return array
     */
    private function createDependenciesFileList(\SplFileInfo $workingDir)
    {
        $loader = require($workingDir->getPathname() . '/vendor/autoload.php');

        if (!defined('APP_GAMEMODULE_PATH')) {
            define('APP_GAMEMODULE_PATH', __DIR__ . '/../../Stubs/');
        }
        require_once(APP_GAMEMODULE_PATH . 'framework.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_Object.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_DbObject.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_Action.inc.php');
        require_once(APP_GAMEMODULE_PATH . 'APP_GameAction.inc.php');

        $config = WorkbenchProjectConfig::loadFromCwd();
        $project = $config->loadProject();
        $path = $project->getGameProjectFile()->getPathname();

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $autoloadFiles = array_values(require($workingDir->getPathname() . '/vendor/composer/autoload_files.php'));
        list($before, $after) = $this->getDependencyFiles($parser, $loader, $path, [$path], [$path]);
        return array_values(
            F\unique(
                array_merge($autoloadFiles, $before, [$path], $after),
                function ($path) {
                    return strtolower(realpath($path));
                }
            )
        );
    }

    /**
     * @param Parser $parser
     * @param ClassLoader $loader
     * @param string $path
     * @param array $lineage
     * @param array $alreadySeen
     * @return array
     */
    private function getDependencyFiles(Parser $parser, ClassLoader $loader, $path, array $lineage, array $alreadySeen)
    {
        $parsed = $parser->parse(file_get_contents($path));

        $visitor = new NameAccumulatorNodeVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);
        $traverser->traverse($parsed);

        $uniqueNames = F\unique($visitor->names, function (Name $name) {
            return $name->toString();
        });
        $allSubPaths = F\unique(
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
            )
        );
        $newSubPaths = F\filter(
            $allSubPaths,
            function ($path) use ($alreadySeen) {
                return !in_array($path, $alreadySeen, true);
            }
        );

        $current = [];
        foreach ($newSubPaths as $subPath) {
            list($newBefore, $newAfter) = $this->getDependencyFiles(
                $parser,
                $loader,
                $subPath,
                array_merge($lineage, [$subPath]),
                array_merge($alreadySeen, $current, [$subPath])
            );
            $current = array_merge($current, $newBefore, [$subPath], $newAfter);
        }

        $sharedLineageAndDeps = array_intersect($allSubPaths, $lineage);
        if (empty($sharedLineageAndDeps)) {
            return [$current, []];
        }

        return [[], $current];
    }
}
