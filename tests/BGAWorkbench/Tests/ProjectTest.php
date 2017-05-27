<?php

namespace TheBattleForHill218\Tests;

use BGAWorkbench\Project;
use PhpOption\Some;
use PHPUnit\Framework\TestCase;
use Qaribou\Collection\ImmArray;
use Symfony\Component\Finder\SplFileInfo;

class ProjectTest extends TestCase
{
    /**
     * @var Project
     */
    private $project;

    protected function setUp()
    {
        $this->project = new Project(
            new \SplFileInfo(realpath(__DIR__ . '/../../..')),
            'battleforhilldhau',
            ImmArray::fromArray([])
        );
    }

    public function testDeveloperLocations()
    {
        assertThat(
            $this->project->getDevelopmentLocations()->toArray(),
            containsInAnyOrder(
                ImmArray::fromArray([
                    'img',
                    'img/game_box.png',
                    'img/game_box180.png',
                    'img/game_box75.png',
                    'img/game_icon.png',
                    'img/publisher.png',
                    'battleforhilldhau.css',
                    'battleforhilldhau.js',
                    'battleforhilldhau.game.php',
                    'battleforhilldhau.action.php',
                    'battleforhilldhau.view.php',
                    'battleforhilldhau_battleforhilldhau.tpl',
                    'states.inc.php',
                    'stats.inc.php',
                    'material.inc.php',
                    'gameoptions.inc.php',
                    'gameinfos.inc.php',
                    'dbmodel.sql',
                    'version.php'
                ])->map(function($path) {
                    return $this->project->absoluteToProjectRelativeFile(
                        new \SplFileInfo($this->project->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $path)
                    );
                })->toArray()
            )
        );
    }

    public function testAllFiles()
    {
        assertThat(
            $this->project->getAllFiles()->toArray(),
            containsInAnyOrder(
                ImmArray::fromArray([
                    'img/game_box.png',
                    'img/game_box180.png',
                    'img/game_box75.png',
                    'img/game_icon.png',
                    'img/publisher.png',
                    'img/cards.png',
                    'battleforhilldhau.css',
                    'battleforhilldhau.js',
                    'battleforhilldhau.game.php',
                    'battleforhilldhau.action.php',
                    'battleforhilldhau.view.php',
                    'battleforhilldhau_battleforhilldhau.tpl',
                    'states.inc.php',
                    'stats.inc.php',
                    'material.inc.php',
                    'gameoptions.inc.php',
                    'gameinfos.inc.php',
                    'dbmodel.sql',
                    'version.php'
                ])->map(function($path) {
                    return $this->project->absoluteToProjectRelativeFile(
                        new \SplFileInfo($this->project->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $path)
                    );
                })->toArray()
            )
        );
    }

    public function testRequiredFiles()
    {
        assertThat(
            $this->project->getRequiredFiles()->toArray(),
            containsInAnyOrder(
                ImmArray::fromArray([
                    'img/game_box.png',
                    'img/game_box180.png',
                    'img/game_box75.png',
                    'img/game_icon.png',
                    'img/publisher.png',
                    'battleforhilldhau.css',
                    'battleforhilldhau.js',
                    'battleforhilldhau.game.php',
                    'battleforhilldhau.action.php',
                    'battleforhilldhau.view.php',
                    'battleforhilldhau_battleforhilldhau.tpl',
                    'states.inc.php',
                    'stats.inc.php',
                    'material.inc.php',
                    'gameoptions.inc.php',
                    'gameinfos.inc.php',
                    'dbmodel.sql',
                    'version.php'
                ])->map(function($path) {
                    return $this->project->absoluteToProjectRelativeFile(
                        new \SplFileInfo($this->project->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $path)
                    );
                })->toArray()
            )
        );
    }

    public function testGetFileVariableValue()
    {
        assertThat(
            $this->project->getFileVariableValue('version.php', 'game_version_battleforhilldhau'),
            equalTo(new Some('999999-9999'))
        );
    }

    public function testAbsoluteToProjectRelativeFile()
    {
        $fullPath = $this->project->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'cards.png';
        $versionFile = new \SplFileInfo($fullPath);
        assertThat(
            $this->project->absoluteToProjectRelativeFile($versionFile),
            equalTo(new SplFileInfo($fullPath, 'img', 'img' . DIRECTORY_SEPARATOR . 'cards.png'))
        );
    }

    public function testAbsoluteToProjectRelativeFileInvalid()
    {
        $this->expectException('RuntimeException');

        $tempDir = new \SplFileInfo(sys_get_temp_dir());
        $this->project->absoluteToProjectRelativeFile($tempDir);
    }
}