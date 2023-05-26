<?php


namespace CortexPE\std;


final class AsyncCallback
{
    /** @var callable */
    private $onSuccess;
    /** @var callable */
    private $onFailure;

    public function __construct(callable $onSuccess, callable $onFailure)
    {
        $this->onSuccess = $onSuccess;
        $this->onFailure = $onFailure;
    }

    /**
     * Only call if the operation was a success
     *
     * @param callable $callable
     * @return static
     */
    public static function success(callable $callable): self
    {
        return new self($callable, function () {
        });
    }

    /**
     * Only call if the operation failed
     *
     * @param callable $callable
     * @return static
     */
    public static function failure(callable $callable): self
    {
        return new self(function () {
        }, $callable);
    }

    public function onSuccess(...$args): void
    {
        ($this->onSuccess)(...$args);
    }

    public function onFailure(...$args): void
    {
        ($this->onFailure)(...$args);
    }
}