<?php

namespace TheBattleForHill218\Functional;

use Functional as F;

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
