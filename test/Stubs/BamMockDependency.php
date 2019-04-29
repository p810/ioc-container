<?php

namespace p810\Container\Test\Stubs;

class BamMockDependency
{
    function __construct(FooMockDependency $foo, BarMockDependency $bar) {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo(): FooMockDependency {
        return $this->foo;
    }

    public function getBar(): BarMockDependency {
        return $this->bar;
    }
}
