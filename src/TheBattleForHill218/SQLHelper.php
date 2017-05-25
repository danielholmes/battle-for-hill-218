<?php

namespace TheBattleForHill218;

class SQLHelper
{
    /**
     * @param string $table
     * @param array $values
     * @return string
     */
    public static function insert($table, array $values)
    {
        $keys = join(', ', array_keys($values));
        $escapedValues = join(
            ',',
            array_map(
                function($value) {
                    return "'" . addslashes($value) . "'";
                },
                array_values($values)
            )
        );
        return "INSERT INTO {$table} ({$keys}) VALUES ({$escapedValues})";
    }
}