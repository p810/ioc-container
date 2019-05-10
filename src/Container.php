<?php

namespace p810\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

use function array_key_exists;

class Container
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
     * @param string $className
     * @param array  $arguments
     * @return object
     */
    public function get(string $className, ...$arguments): object
    {
        $entry = $this->entry($className);

        if ($entry->isSingleton()) {
            return $entry->getInstance();
        }

        return $entry->make(...$arguments);
    }

    /**
     * Creates a new entry in the container for the given class
     * 
     * @param string        $className The fully qualified name of the class to be stored
     * @param null|callable $factory   An optional callback that can be used to create the object
     * @param bool          $concrete  Whether this is a concrete entry, i.e. a singleton instance
     * @param null|object   $instance  An optional instance for singletons
     * @return \p810\Container\Entry
     */
    public function set(
        string    $className,
        ?callable $factory   = null,
        bool      $concrete  = false,
        ?object   $instance  = null): Entry
    {
        if (! $factory) {
            $factory = [$this, 'resolve'];
        }

        $entry = $this->classes[$className] = new Entry($className, $factory, $concrete, $instance);

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
     * @param string      $className
     * @param null|object $instance
     * @return object
     */
    public function singleton(string $className, ?object $instance = null): object
    {
        $instance = $instance ?? $this->resolve($className);

        $this->set($className, null, true, $instance);

        return $instance;
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
