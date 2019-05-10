<?php

namespace p810\Container\Test\Stubs;

class QuuxMockDependency
{
    protected $message;

    function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
