<?php


namespace CortexPE\std;


use Exception;
use ReflectionClass;

final class AbstractConstants
{
    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public static function __check(string $class, string $c): void
    {
        // https://stackoverflow.com/a/10368627/7126351
        $reflection = new ReflectionClass($class);
        $constantsForced = $reflection->getConstants();
        foreach ($constantsForced as $constant => $value) {
            if (constant("$c::$constant") == "abstract") {
                throw new Exception("Undefined $constant in $c");
            }
        }
    }
}