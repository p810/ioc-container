<?php

namespace p810\Container;

use function is_a;
use function is_array;
use function array_unshift;

class Entry
{
    /**
     * @var bool
     */
    protected $isConcrete;

    /**
     * @var callable
     */
    protected $factory;

    /**
     * @var null|object
     */
    protected $instance;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param string        $className
     * @param null|callable $factory
     * @param bool          $isConcrete
     * @param null|object   $instance
     * @return void
     */
    function __construct(
        string    $className,
        ?callable $factory    = null,
        bool      $isConcrete = false,
        ?object   $instance   = null)
    {
        $this->factory    = $factory;
        $this->instance   = $instance;
        $this->className  = $className;
        $this->isConcrete = $isConcrete;
    }

    /**
     * Returns an instance of the injected class
     * 
     * @param array $arguments
     * @return object
     */
    public function make(...$arguments): object
    {
        if ($this->factoryIsResolver()) {
            array_unshift($arguments, $this->className);
        }

        return ($this->factory)(...$arguments);
    }

    /**
     * Returns a boolean indicating whether the entry is a singleton
     * 
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->isConcrete;
    }

    /**
     * Returns a singleton instance of this class, or null
     * 
     * @return null|object
     */
    public function getInstance(): ?object
    {
        return $this->instance;
    }

    /**
     * Returns a boolean indicating whether the given callback is a class that implements
     * \p810\Container\DependencyResolverInterface
     * 
     * @return bool
     */
    protected function factoryIsResolver(): bool
    {
        return is_array($this->factory) && is_a($this->factory[0], DependencyResolverInterface::class, true);
    }
}
