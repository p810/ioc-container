<?php

namespace p810\Container;

/**
 * Classes that implement this interface should be able to instantiate classes and their dependencies to automate dependency injection.
 */
interface Resolver
{
    /**
     * Instantiates a class and its dependencies
     * 
     * @param string     $className
     * @param null|array $arguments
     * @return object
     */
    public function resolve(string $className, array $arguments = []): object;
}
