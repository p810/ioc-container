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
     * @param string     $className The fully qualified class name to resolve
     * @param null|array $arguments An optional array of arguments for the constructor of the class being resolved
     * @return object
     */
    public function resolve(string $className, ...$arguments): object;
}
