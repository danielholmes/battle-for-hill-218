<?php

namespace BGAWorkbench\Test;

use Hamcrest\Matcher;

// TODO: Convert to namespaced functions when have time to work it out
class HamcrestMatchers
{
    /**
     * @param array $entries
     * @return Matcher
     */
    public static function hasEntries(array $entries)
    {
        return allOf(array_map('hasEntry', array_keys($entries), $entries));
    }

    /**
     * TODO: containsKeys would be useful
     * @param array $keys
     * @return Matcher
     */
    public static function hasKeys(array $keys)
    {
        return allOf(array_map('hasKey', $keys));
    }
}