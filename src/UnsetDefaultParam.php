<?php

namespace p810\Container;

class UnsetDefaultParam
{
    static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        self::$instance = new UnsetDefaultParam;

        return self::$instance;
    }
}
