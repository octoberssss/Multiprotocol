<?php


namespace CortexPE\std;


/**
 * This class lets you respond once to an action, and never again for the specified time
 **/
class ResponseLimiter
{
    private $callback;
    private $cooldownTime;
    private $cooldowns = [];

    public function __construct(int $cooldownTime, callable $callback)
    {
        $this->cooldownTime = $cooldownTime;
        $this->callback = $callback;
    }

    public function respond(string $index, ...$args): void
    {
        if (($this->cooldowns[$index] ?? 0) - time() >= 0) return;
        ($this->callback)(...$args);
        $this->cooldowns[$index] = time() + $this->cooldownTime;
    }
}