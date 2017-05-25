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
        $quotedKeys = join(', ', array_map(array(__CLASS__, 'quoteField'), array_keys($values)));
        $escapedValues = join(
            ', ',
            array_map(array(__CLASS__, 'quoteValue'), array_values($values))
        );
        $quotedTable = self::quoteField($table);
        return "INSERT INTO {$quotedTable} ({$quotedKeys}) VALUES ({$escapedValues})";
    }

    /**
     * @param string $field
     * @return string
     */
    private static function quoteField($field)
    {
        return '`' . $field . '`';
    }

    /**
     * @param string|boolean|null $value
     * @return string
     */
    private static function quoteValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value)) {
            return strval($value);
        }
        if (is_bool($value)) {
            return self::quoteValue((int) $value);
        }
        if (is_float($value)) {
            return strval($value);
        }
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        $valueType = gettype($value);
        throw new \RuntimeException("Unknown value type {$valueType}");
    }
}