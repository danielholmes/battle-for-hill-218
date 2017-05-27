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
    private static $cwdConfig = null;

    /**
     * @return Project
     */
    private static function getCwdProjectConfig()
    {
        if (self::$cwdConfig === null) {
            self::$cwdConfig = ProjectWorkbenchConfig::loadFromCwd();
        }

        return self::$cwdConfig;
    }

    /**
     * @return GameTableInstanceBuilder
     */
    protected static function gameTableInstanceBuilder()
    {
        return GameTableInstanceBuilder::create(self::getCwdProjectConfig());
    }
}