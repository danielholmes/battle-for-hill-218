<?php

$loader = require(__DIR__ . '/vendor/autoload.php');

define('APP_GAMEMODULE_PATH', __DIR__ . '/src/BGAWorkbench/Stubs/');
require_once(APP_GAMEMODULE_PATH . 'framework.php');
require_once(APP_GAMEMODULE_PATH . 'APP_Object.inc.php');
require_once(APP_GAMEMODULE_PATH . 'APP_DbObject.inc.php');
require_once(APP_GAMEMODULE_PATH . 'APP_Action.inc.php');
require_once(APP_GAMEMODULE_PATH . 'APP_GameAction.inc.php');

use BGAWorkbench\Project\WorkbenchProjectConfig;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Functional as F;


$config = WorkbenchProjectConfig::loadFromCwd();
$project = $config->loadProject();


class MyNodeVisitor extends NodeVisitorAbstract
{
    public $names = [];

    public function leaveNode(Node $node) {
        if ($node instanceof UseUse) {
            $this->names[] = $node->name;
        }
        if ($node instanceof Class_) {
            if ($node->extends) {
                $this->names[] = $node->extends;
            }
            if ($node->implements) {
                $this->names = array_merge($this->names, $node->implements);
            }
        }
        if ($node instanceof Interface_) {
            $this->names = array_merge($this->names, $node->extends);
        }
    }
}

$path = $project->getDirectory()->getPathname() . '/' . $project->getGameProjectFileRelativePathname();
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

function getFiles($parser, $loader, $path) {
    $parsed = $parser->parse(file_get_contents($path));

    $visitor = new MyNodeVisitor();
    $traverser = new NodeTraverser();

    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor($visitor);

    $traverser->traverse($parsed);
    $subPaths = F\unique(
        F\filter(
            F\map(
                $visitor->names,
                function(Name $name) use ($loader) {
                    return $loader->findFile($name->toString());
                }
            ),
            function($path) { return $path; }
        )
    );

    return array_merge(
        F\unique(
            F\flat_map(
                $subPaths,
                function($subPath) use ($parser, $loader) {
                    return getFiles($parser, $loader, $subPath);
                }
            )
        ),
        [$path]
    );
}

return getFiles($parser, $loader, $path);
