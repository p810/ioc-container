<?php

namespace p810\Container;

/**
 * Represents a case where a user has not specified a default value for a parameter in
 * an object's constructor
 */
class UnsetDefaultParam
{
    /**
     * @var \p810\Container\UnsetDefaultParam
     */
    static $instance;

    /**
     * Returns a single instance of this class
     * 
     * @return \p810\Container\UnsetDefaultParam
     */
    public static function getInstance()
    {
        if (! self::$instance instanceof UnsetDefaultParam) {
            self::$instance = new UnsetDefaultParam;
        }

        return self::$instance;
    }

    /**
     * Prevent direct instantiation and cloning of this object
     * 
     * @return void
     */
    private function __clone()
    {}
    private function __construct()
    {}
}
