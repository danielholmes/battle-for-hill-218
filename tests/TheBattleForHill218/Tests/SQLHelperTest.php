<?php

namespace TheBattleForHill218\Tests;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\SQLHelper;

class SQLHelperTest extends TestCase
{
    public function testBasicInsert()
    {
        assertThat(
            SQLHelper::insert('person', array('name' => 'Daniel Holmes')),
            equalTo("INSERT INTO `person` (`name`) VALUES ('Daniel Holmes')")
        );
    }

    public function testInsertQuotesTypes()
    {
        assertThat(
            SQLHelper::insert(
                'game',
                array(
                    'name' => "Johnny's Quest",
                    'category' => null,
                    'fun' => true,
                    'hard' => false,
                    'year' => 2017,
                    'weight' => 1.65
                )
            ),
            equalTo("INSERT INTO `game` (`name`, `category`, `fun`, `hard`, `year`, `weight`) VALUES ('Johnny\\'s Quest', NULL, 1, 0, 2017, 1.65)")
        );
    }

    public function testInsertInvalidValue()
    {
        $this->expectException('RuntimeException');
        SQLHelper::insert('person', array('name' => $this));
    }
}