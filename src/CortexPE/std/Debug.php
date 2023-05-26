<?php


namespace CortexPE\std;


final class Debug
{
    private function __construct()
    {
    }

    public static function getCaller(int $depth = 0)
    {
        return debug_backtrace(~DEBUG_BACKTRACE_PROVIDE_OBJECT)[2 + $depth];
    }
}