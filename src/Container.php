<?php

namespace p810\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

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

    function __construct(DependencyResolverInterface $resolver) {
        $this->resolver = $resolver;
    }

    public function has(string $className): bool {
        return array_key_exists($className, $this->classes);
    }

    public function get(string $className, ...$arguments): object {
        if (! $this->has($className)) {
            $this->set($className);
        }

        if ($this->isSingleton($className)) {
            return $this->getSingleton($className);
        }

        $callback = $this->classes[$className]['factory'];

        if (! $callback instanceof Closure) {
            array_unshift($arguments, $className);
        }

        return $callback(...$arguments);
    }

    public function set(string $className, ?callable $factory = null): void {
        $this->classes[$className] = [
            'isConcrete' => false,
            'className'  => $className,
            'factory'    => $factory ?? [$this->resolver, 'resolve'],
            'instance'   => null
        ];
    }

    public function isSingleton(string $className): bool {
        return $this->classes[$className]['isConcrete'];
    }

    public function getSingleton(string $className): object {
        return $this->classes[$className]['instance'];
    }

    public function singleton(string $className): object {
        $instance = $this->resolver->resolve($className);

        $this->classes[$className] = [
            'isConcrete' => true,
            'className'  => $className,
            'factory'    => null,
            'instance'   => $instance
        ];

        return $instance;
    }

    public function bind(string $interfaceName, string $className): void {
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
}
