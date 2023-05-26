<?php


namespace CortexPE\std;


use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;

final class libasynqlAwaitWrapper
{
    /** @var DataConnector */
    private $database;

    public function __construct(DataConnector $connector)
    {
        $this->database = $connector;
    }

    public function asyncSelect(string $query, array $args = []): \Generator
    {
        $this->database->executeSelect($query, $args, yield, yield Await::REJECT);
        return yield Await::ONCE;
    }

    public function asyncInsert(string $query, array $args): \Generator
    {
        $this->database->executeInsert($query, $args, yield, yield Await::REJECT);
        return yield Await::ONCE;
    }

    public function asyncGeneric(string $query, array $args = []): \Generator
    {
        $this->database->executeGeneric($query, $args, yield, yield Await::REJECT);
        return yield Await::ONCE;
    }

    public function asyncChange(string $query, array $args = []): \Generator
    {
        $this->database->executeChange($query, $args, yield, yield Await::REJECT);
        return yield Await::ONCE;
    }

    public function asyncSelectRaw(string $query, array $args = []): \Generator
    {
        $this->database->executeSelectRaw($query, $args, yield, yield Await::REJECT);
        return yield Await::ONCE;
    }
}