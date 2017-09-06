<?php

namespace TheBattleForHill218\Functional;

use Functional as F;

/**
 * @param \Traversable|array $collection
 * @param callable $callback
 * @return array
 */
function group_to_lists($collection, $callback)
{
    return F\map(
        F\group($collection, $callback),
        function (array $map) {
            return array_values($map);
        }
    );
}
