<?php

namespace p810\Container;

use ReflectionClass;
use ReflectionParameter;

use function count;
use function sprintf;
use function is_array;
use function preg_match;
use function array_key_exists;

class ReflectionContainer extends Container implements Resolver
{
    /**
     * Instantiates and returns an object of the given class using the Reflection API
     * 
     * @param string $className A fully qualified class name
     * @param array  $arguments Optional arguments for the constructor of the class being resolved
     * @return object
     * @throws \p810\Container\UnresolvableArgumentException if one of the class's constructor's arguments could not be resolved
     */
    public function resolve(string $className, ...$arguments): object
    {
        $entry = $this->entry($className);
        
        $reflector = new ReflectionClass($entry->getClassName());
        $constructor = $reflector->getConstructor();

        if (! $constructor) {
            return $reflector->newInstance();
        }

        $arguments = $this->getConstructorArguments($constructor->getParameters(), $entry, $arguments, $constructor->getDocComment());

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * Iterates over the parameters in a class's constructor and attempts to resolve them so the class can be
     * instantiated by the Reflection API
     * 
     * @param \ReflectionParameter[] $parameters A list of \ReflectionParameter objects from a class's constructor
     * @param \p810\Container\Entry  $entry      The \p810\Container\Entry object representing the class being instantiated
     * @param array                  $arguments  Optional default arguments for the constructor
     * @param null|string            $docblock   The constructor's docblock, if applicable
     * @return mixed[]
     * @throws \p810\Container\UnresolvableArgumentException if a parameter in a class's constructor could not be instantiated
     */
    protected function getConstructorArguments(array $parameters, Entry $entry, array $arguments, ?string $docblock): array
    {
        $i = 0;
        $dependencies = [];

        foreach ($parameters as $key => $parameter) {
            $name = $parameter->getName();
            $value = $this->getDefaultArgument($name, $i, $entry, $arguments);

            if ($value instanceof MissingDefaultParameter) {
                $value = $this->resolveClassFromParameter($parameter)
                    ?? ($docblock ? $this->resolveClassFromDocComment($parameter->getName(), $docblock) : null);
  
                if (! $value) {
                    throw new UnresolvableArgumentException("A constructor argument ({$parameter->getName()}) could not be resolved to a usable value");
                }
            }

            ++$i;
            $dependencies[$key] = $value;
        }

        return $dependencies;
    }

    /**
     * @param string                $name
     * @param int                   $index
     * @param \p810\Container\Entry $entry
     * @param array                 $arguments
     * @return mixed|\p810\Container\MissingDefaultParameter
     */
    protected function getDefaultArgument(string $name, int $index, Entry $entry, array $arguments)
    {
        if ($arguments) {
            if (
                count($arguments) === 1 &&
                is_array($arguments[0]) &&
                array_key_exists($name, $arguments[0])
            ) {
                return $arguments[0][$name];
            }

            if (array_key_exists($index, $arguments)) {
                return $arguments[$index];
            }
        }

        return $entry->getParam($name);
    }

    /**
     * Attempts to resolve a class from the type hint of the given parameter, and returns
     * the instantiated object if successful. Otherwise returns null.
     * 
     * @param \ReflectionParameter $parameter
     * @return null|object
     */
    protected function resolveClassFromParameter(ReflectionParameter $parameter): ?object
    {
        $class = $parameter->getClass();

        if (! $class) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            return null;
        }

        return $this->resolve($class->getName());
    }

    /**
     * Attempts to extract a fully qualified class name for a variable (param) from the given docblock
     * and returns an instance of that class if the pattern matched. Otherwise, returns null.
     * 
     * @param string $parameterName The variable (or param) in the docblock to search for
     * @param string $comment       The docblock belonging to the class that's dependent on the object
     *                              returned from this method
     * @return null|object
     */
    protected function resolveClassFromDocComment(string $parameterName, string $comment): ?object
    {
        $pattern = sprintf('/\s*@param\s*([A-Za-z0-9\\\_]+)\s*\$%s/', $parameterName);
        $matched = preg_match($pattern, $comment, $matches);

        if (! $matched) {
            return null;
        }

        return $this->resolve($matches[1]);
    }
}
