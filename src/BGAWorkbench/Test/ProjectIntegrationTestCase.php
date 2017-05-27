<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project;
use BGAWorkbench\ProjectWorkbenchConfig;
use PHPUnit\Framework\TestCase;

class ProjectIntegrationTestCase extends TestCase
{
    /**
     * @var Project|null
     */
    private static $cwdProject = null;

    /**
     * @return Project
     */
    private static function getCwdProject()
    {
        if (self::$cwdProject === null) {
            self::$cwdProject = ProjectWorkbenchConfig::loadFromCwd()->loadProject();
        }

        return self::$cwdProject;
    }

    /**
     * @return GameTableInstanceBuilder
     */
    protected static function gameTableInstanceBuilder()
    {
        return GameTableInstanceBuilder::create(self::getCwdProject());
    }
}