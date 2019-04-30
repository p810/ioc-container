<?php

namespace p810\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

use function is_a;
use function is_array;
use function array_unshift;
use function array_key_exists;

class Container
{
    /**
     * @var array<string,array>
     * @param bool        $isConcrete Specifies whether the entry should return a single object, or create a new one each time
     * @param callable    $factory    The method used to instantiate and return instances of the entry's class
     * @param string      $className  Name of the class represented by the entry
     * @param null|object $instance   (Optional) A class instance used for singletons, if $isConcrete === true
     */
    protected $classes = [];

    /**
     * @var \p810\Container\DependencyResolverInterface
     */
    protected $resolver;

    /**
     * Injects a \p810\Container\DependencyResolverInterface for the container to use to instantiate classes
     * 
     * @param \p810\Container\DependencyResolverInterface $resolver
     */
    function __construct(DependencyResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Returns a boolean indicating whether the container has an instance of the requested class
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
        if (! $this->has($className)) {
            $this->set($className);
        }

        if ($this->isSingleton($className)) {
            return $this->getSingleton($className);
        }

        $callback = $this->classes[$className]['factory'];

        if ($this->factoryIsResolver($callback)) {
            array_unshift($arguments, $className);
        }

        return $callback(...$arguments);
    }

    /**
     * Creates a new entry in the container for the given class
     * 
     * @param string        $className
     * @param null|callable $factory   An optional callback that can be used to create the object
     * @return void
     */
    public function set(string $className, ?callable $factory = null): void
    {
        $this->classes[$className] = [
            'isConcrete' => false,
            'className'  => $className,
            'factory'    => $factory ?? [$this->resolver, 'resolve'],
            'instance'   => null
        ];
    }

    /**
     * Returns a boolean indicating whether the given class in the container is a singleton
     * 
     * @param string $className
     * @return bool
     */
    public function isSingleton(string $className): bool
    {
        return $this->classes[$className]['isConcrete'];
    }

    /**
     * Returns a single instance of a class from the container
     * 
     * @param string $className
     * @return object
     */
    public function getSingleton(string $className): object
    {
        return $this->classes[$className]['instance'];
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
        $instance = $instance ?? $this->resolver->resolve($className);

        $this->classes[$className] = [
            'isConcrete' => true,
            'className'  => $className,
            'factory'    => null,
            'instance'   => $instance
        ];

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
        $this->resolver->bind($interfaceName, $className);

        if ($this->has($className) && $this->isSingleton($className)) {
            $this->classes[$interfaceName] = [
                'isConcrete' => true,
                'className'  => $interfaceName,
                'factory'    => null,
                'instance'   => $this->getSingleton($className)
            ];
        }
    }

    /**
     * Returns a boolean indicating whether the given callback is a class that implements
     * \p810\Container\DependencyResolverInterface
     * 
     * @param callable $factory
     * @return bool
     */
    protected function factoryIsResolver(callable $factory): bool
    {
        return is_array($factory) && is_a($factory[0], DependencyResolverInterface::class, true);
    }
}
