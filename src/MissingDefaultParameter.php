<?php

namespace p810\Container;

/**
 * Represents a case where a user has not specified a default value for a parameter in an object's constructor.
 */
class MissingDefaultParameter
{
    /**
     * @var \p810\Container\MissingDefaultParameter
     */
    static $instance;

    /**
     * Returns a single instance of this class
     * 
     * @return \p810\Container\MissingDefaultParameter
     */
    public static function getInstance()
    {
        if (! self::$instance instanceof UnsetDefaultParam) {
            self::$instance = new MissingDefaultParameter;
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
