<?php


namespace BGAWorkbench;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use Qaribou\Collection\ImmArray;

class Utils
{
    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    public static function getVariableNameFromFile(\SplFileInfo $file, $namePredicate)
    {
        return self::getVariableFromFile($file, $namePredicate)
            ->map(function(array $variable) {
                list($name, $value) = $variable;
                return $name;
            });
    }

    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    public static function getVariableValueFromFile(\SplFileInfo $file, $namePredicate)
    {
        return self::getVariableFromFile($file, $namePredicate)
            ->map(function(array $variable) {
                list($name, $value) = $variable;
                return $value;
            });
    }

    /**
     * @param \SplFileInfo $file
     * @param string|callable $namePredicate
     * @return Option
     */
    private static function getVariableFromFile(\SplFileInfo $file, $namePredicate)
    {
        if (!$file->isReadable()) {
            throw new \InvalidArgumentException("Couldn't open file {$file->getPathname()}");
        }

        if (is_string($namePredicate)) {
            $stringNeedle = $namePredicate;
            $namePredicate = function($name) use ($stringNeedle) { return $name === $stringNeedle; };
        }

        include($file->getPathname());
        $definedVars = get_defined_vars();
        return ImmArray::fromArray(array_keys($definedVars))
            ->reduce(
                function(Option $current, $name) use ($namePredicate, $definedVars) {
                    if ($namePredicate($name)) {
                        return new Some(array($name, $definedVars[$name]));
                    }
                    return $current;
                },
                None::create()
            );
    }
}