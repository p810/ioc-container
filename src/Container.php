<?php

namespace p810\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

use function is_object;
use function array_key_exists;

/**
 * A base class that acts as a container for classes that should be autowired by the implementing Resolver.
 */
abstract class Container
{
    /**
     * @var \p810\Container\Entry[]
     */
    protected $classes = [];

    /**
     * Returns a boolean indicating whether the container has an entry for the given class
     * 
     * @param string $className
     * @return bool
     */
    public function has(string $className): bool
    {
        return array_key_exists($className, $this->classes);
    }

    /**
     * Returns an object from the container
     * 
     * @param string     $className The fully qualified class name to resolve
     * @param array $arguments An optional, associative array of named arguments (params)
     * @return object
     */
    public function get(string $className, array $arguments = []): object
    {
        return $this->entry($className)->make($arguments);
    }

    /**
     * Creates a new entry in the container for the given class
     * 
     * @param string        $className   The fully qualified name of the class to be stored
     * @param null|callable $factory     An optional callback that can be used to create the object
     * @param null|object   $instance    An optional instance for singletons
     * @param bool          $isSingleton Whether this entry represents a singleton
     * @return \p810\Container\Entry
     */
    public function set(string $className, ?callable $factory = null, ?object $instance = null, bool $isSingleton = false): Entry
    {
        if (! $factory) {
            $factory = [$this, 'resolve'];
        }

        $entry = $this->classes[$className] = new Entry($className, $factory, ($factory || $isSingleton), $instance);

        return $entry;
    }

    /**
     * Returns the container entry for a given class, if it exists
     * 
     * @param string $className
     * @return null|\p810\Container\Entry
     */
    public function entry(string $className): ?Entry
    {
        if ($this->has($className)) {
            return $this->classes[$className];
        }

        return $this->set($className);
    }

    /**
     * Creates a singleton entry in the container and returns the instance
     * 
     * @param string        $className
     * @param null|object   $instance
     * @param null|callable $factory
     * @param bool          $resolveNow
     * @return \p810\Container\Entry
     */
    public function singleton(string $className, ?object $instance = null, ?callable $factory = null, bool $resolveNow = false): Entry
    {
        $entry = $this->set($className, $factory, $instance, true);

        if (! $instance && $resolveNow) {
            $entry->make();
        }

        return $entry;
    }

    /**
     * Associates a given interface with a specific implementation of itself, to be returned whenever
     * the interface is requested from the container
     * 
     * @param string $interfaceName
     * @param string $className
     * @return void
     */
    public function bind(string $interfaceName, string $className): void
    {
        $this->classes[$interfaceName] = $this->entry($className);
    }
}
