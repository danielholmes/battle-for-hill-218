<?php

namespace BGAWorkbench\Commands;

use BGAWorkbench\Project\Project;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\ResourceWatcher\Tracker;
use JasonLewis\ResourceWatcher\Watcher;
use BGAWorkbench\Project\WorkbenchProjectConfig;
use BGAWorkbench\Utils\NameAccumulatorNodeVisitor;
use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\ProcessBuilder;
use PhpParser\Node\Name;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Functional as F;

class BuildCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the project')
            ->addOption('watch', 'w', null, 'Watch src files and continually build');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = WorkbenchProjectConfig::loadFromCwd();
        $project = $config->loadProject();
        if ($input->getOption('watch')) {
            $this->runBuild($project);
            $this->executeWatch($project, $output);
            return 0;
        }

        try {
            $outputFilepath = $this->runBuild($project);
        } catch (\Exception $e) {
            $output->write('<error>' . $e->getMessage() . '</error>');
            return -1;
        }
        $output->writeln("<info>Built to {$outputFilepath}</info>");
    }

    /**
     * @param Project $project
     * @param OutputInterface $output
     */
    private function executeWatch(Project $project, OutputInterface $output)
    {
        $files = new Filesystem();
        $tracker = new Tracker();
        $watcher = new Watcher($tracker, $files);
        $handler = function ($resource, $path) use ($project, $output) {
            $output->write('Changed: ' . $path);
            $this->runBuild($project);
            $output->writeln(' <info>✓</info>');
        };
        F\each(
            $project->getDevelopmentLocations(),
            function (SplFileInfo $file) use ($watcher, $handler) {
                $listener = $watcher->watch($file->getPathname());
                $listener->onCreate($handler);
                $listener->onModify($handler);
                $listener->onDelete($handler);
            }
        );
        $watcher->start(500000);
    }

    /**
     * @param Project $project
     * @return string
     */
    private function runBuild(Project $project)
    {
        // TODO: Generalise to both project types
        $workingDir = $project->buildProdVendors();

        $configFilepath = $project->getBuildDirectory()->getPathname() . '/compiler-config.php';
        $files = $this->createDependenciesFileList($workingDir);
        file_put_contents($configFilepath, '<?php return ' . var_export($files, true) . ';');
        $outputFilepath = $project->getDistDirectory()->getPathname() . '/' .
            $project->getGameProjectFileRelativePathname();

        $process = ProcessBuilder::create([
            'classpreloader.php',
            'compile',
            '--config=' . $configFilepath,
            '--output=' . $outputFilepath,
            '--strip_comments=1'
        ])->getProcess();
        $result = $process->run();
        if ($result !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $outputFilepath;
    }

    /**
     * @param \SplFileInfo $workingDir
     * @return array
     */
    private function createDependenciesFileList(\SplFileInfo $workingDir)
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

        $config = WorkbenchProjectConfig::loadFromCwd();
        $project = $config->loadProject();

        $path = $project->getDirectory()->getPathname() . '/' . $project->getGameProjectFileRelativePathname();
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
