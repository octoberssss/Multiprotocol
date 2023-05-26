<?php


namespace CortexPE\std;


use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;

class SanitizedNameArgument extends RawStringArgument
{
    public function getTypeName(): string
    {
        return "name";
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return ctype_alnum($testString) && strlen($testString) <= 10 && strlen($testString) >= 3;
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $argument;
    }
}