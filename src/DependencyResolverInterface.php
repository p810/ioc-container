<?php

namespace p810\Container;

/**
 * Classes that implement this interface should be able to instantiate classes and their dependencies to automate dependency injection.
 */
interface DependencyResolverInterface
{
    /**
     * Instantiates a class and its dependencies.
     */
    public function resolve(string $className): object;

    /**
     * Binds an interface to a specific implementation.
     */
    public function bind(string $interfaceName, string $className): void;
}