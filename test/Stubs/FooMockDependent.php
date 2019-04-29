<?php

namespace p810\Container\Test\Stubs;

class FooMockDependent
{
    function __construct(FooMockInterface $foo) {
        $this->foo = $foo;
    }

    public function getFoo(): FooMockInterface {
        return $this->foo;
    }
}
