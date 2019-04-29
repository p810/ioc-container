<?php

namespace p810\Container\Test\Stubs;

class FooMockSingleton
{
    protected $bar;

    function __construct(BarMockDependency $bar) {
        $this->bar = $bar;
    }

    public function getBar() {
        return $this->bar;
    }
}
