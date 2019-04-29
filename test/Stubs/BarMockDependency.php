<?php

namespace p810\Container\Test\Stubs;

class BarMockDependency
{
    function __construct(FooMockDependency $foo) {
        $this->foo = $foo;
    }

    public function getFoo(): FooMockDependency {
        return $this->foo;
    }
}