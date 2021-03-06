<?php

namespace TheBattleForHill218;

use Functional as F;
use TheBattleForHill218\Functional as HF;

class SQLHelper
{
    /**
     * @param string $table
     * @param array $values
     * @return string
     */
    public static function insert(string $table, array $values) : string
    {
        return self::insertAll($table, [$values]);
    }

    /**
     * @param string $table
     * @param array $allValues
     * @return string
     */
    public static function insertAll(string $table, array $allValues) : string
    {
        if (empty($allValues)) {
            throw new \InvalidArgumentException('All Values is empty');
        }

        $allValues = array_values($allValues);
        $allKeys = F\map($allValues, function (array $values) {
            return array_keys($values);
        });
        $uniqueKeys = HF\unique_list($allKeys);
        if (count($uniqueKeys) !== 1) {
            $keysString = json_encode($allKeys);
            throw new \InvalidArgumentException("Must provide unique keys (got {$keysString})");
        }

        $quotedTable = self::quoteField($table);
        $keys = array_values($uniqueKeys[0]);
        $quotedKeys = join(', ', F\map($keys, [__CLASS__, 'quoteField']));
        $valuesList = join(
            ', ',
            F\map(
                $allValues,
                function (array $values) {
                    return '(' . join(', ', F\map($values, [__CLASS__, 'quoteValue'])) . ')';
                }
            )
        );

        return "INSERT INTO {$quotedTable} ({$quotedKeys}) VALUES {$valuesList}";
    }

    /**
     * @param string $field
     * @return string
     */
    public static function quoteField(string $field) : string
    {
        return '`' . $field . '`';
    }

    /**
     * @param string|boolean|null $value
     * @return string
     */
    public static function quoteValue($value) : string
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
