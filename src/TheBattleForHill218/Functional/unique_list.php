<?php

namespace TheBattleForHill218\Functional;

use Functional as F;

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
