<?php

namespace BGAWorkbench\Test;

use BGAWorkbench\Project\Project;
use BGAWorkbench\Project\WorkbenchProjectConfig;
use PHPUnit\Framework\TestCase;

class ProjectIntegrationTestCase extends TestCase
{
    /**
     * @var Project|null
     */
    private static $cwdConfig = null;

    /**
     * @return WorkbenchProjectConfig
     */
    private static function getCwdProjectConfig()
    {
        if (self::$cwdConfig === null) {
            self::$cwdConfig = WorkbenchProjectConfig::loadFromCwd();
        }

        return self::$cwdConfig;
    }

    /**
     * @return TableInstanceBuilder
     */
    protected static function gameTableInstanceBuilder()
    {
        return TableInstanceBuilder::create(self::getCwdProjectConfig());
    }
}