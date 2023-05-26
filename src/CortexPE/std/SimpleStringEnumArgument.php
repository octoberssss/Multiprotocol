<?php


namespace CortexPE\std;


use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class SimpleStringEnumArgument extends StringEnumArgument
{
    private $values;
    private $typeName;

    /**
     * SimpleStringEnumArgument constructor.
     * @param string $name
     * @param string[] $values
     * @param string $typeName
     * @param bool $optional
     */
    public function __construct(string $name, array $values, string $typeName, bool $optional = false)
    {
        $this->values = $values;
        $this->typeName = $typeName;
        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $argument;
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return in_array($testString, $this->values);
    }

    public function getEnumValues(): array
    {
        return $this->values;
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }
}