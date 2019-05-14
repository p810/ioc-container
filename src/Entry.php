<?php

namespace p810\Container;

use function is_a;
use function is_array;
use function array_unshift;
use function array_key_exists;

/**
 * Represents an item in `p810\Container\Container`, i.e. a resolvable class.
 */
class Entry
{
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
     * @var bool
     */
    protected $isSingleton;

    /**
     * @param string        $className
     * @param null|callable $factory
     * @param bool          $isSingleton
     * @param null|object   $instance
     * @return void
     */
    function __construct(string $className, ?callable $factory = null, bool $isSingleton = false, ?object $instance = null)
    {
        $this->factory     = $factory;
        $this->instance    = $instance;
        $this->className   = $className;
        $this->isSingleton = $isSingleton;
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
     * @param string $name  The variable name of the parameter to bind
     * @param mixed  $value Any value to use for the paramter when resolving the class
     * @return self
     */
    public function param(string $name, $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Sets default values for multiple parameters in a constructor's signature
     * 
     * @param array $parameters An associative array mapping param names to their desired default values
     * @return self
     */
    public function params(array $parameters): self
    {
        foreach ($parameters as $name => $value) {
            $this->param($name, $value);
        }

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
     * @param array $arguments An optional, associative array of named arguments (params)
     * @return object
     */
    public function make(array $arguments = []): object
    {
        $instance = $this->getInstance();

        if ($instance === null) {
            $args = [$arguments];
            
            if ($this->factoryIsResolver()) {
                array_unshift($args, $this->className);
            }
    
            $instance = ($this->factory)(...$args);
    
            if ($this->isSingleton()) {
                $this->instance = $instance;
            }
        }

        return $instance;
    }

    /**
     * Returns a boolean indicating whether the entry is a singleton
     * 
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->isSingleton;
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
