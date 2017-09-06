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
