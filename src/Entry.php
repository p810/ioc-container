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
     * Returns the name of the class represented by this entry
     * 
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Sets a default value to be used for the given param by $name
     * 
     * This is useful for cases like when a dependency has a scalar param
     * 
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function param(string $name, $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Returns the default value configured for a given parameter of the constructor in the
     * class represented by this entry. If no default value has been specified, a singleton
     * of \p810\Container\UnsetDefaultParam is returned.
     * 
     * We use this singleton because a user may map a falsey value to a parameter, so we can't
     * check for a usual inidcator of a missing value e.g. null. I felt that a try/catch looked
     * weird, and semantically incorrect. A singleton prevents us from having to create potentially
     * hundreds or thousands of dummy objects that just immediately get thrown aside and fits the
     * program's flow more naturally.
     * 
     * @param string $name The name of the parameter in the constructor
     * @return mixed|\p810\Container\UnsetDefaultParam
     */
    public function getParam(string $name)
    {
        if (! array_key_exists($name, $this->params)) {
            return UnsetDefaultParam::getInstance();
        }

        return $this->params[$name];
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
        return is_array($this->factory) && is_a($this->factory[0], Resolver::class, true);
    }
}
