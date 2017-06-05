<?php

namespace TheBattleForHill218\Functional;

use Functional as F;

/**
 * @param \Traversable|array $collection
 * @param callable $callback
 * @return array
 */
function filter_to_list($collection, $callback)
{
    return array_values(F\filter($collection, $callback));
}

/**
 * @param \Traversable|array $collection
 * @param callable $callback
 * @param bool $strict
 * @return array
 */
function unique_list($collection, $callback = null, $strict = true)
{
    return array_values(F\unique($collection, $callback, $strict));
}

/**
 * @param \Traversable|array $collection
 * @param callable $callback
 * @return array
 */
function partition_to_lists($collection, $callback)
{
    return F\map(F\partition($collection, $callback), function ($array) {
        return array_values($array);
    });
}
