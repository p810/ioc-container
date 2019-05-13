<?php

namespace p810\Container;

use ReflectionClass;
use ReflectionParameter;

class ReflectionContainer extends Container implements Resolver
{
    /**
     * Instantiates and returns an object of the given class using the Reflection API
     * 
     * @param string $className A fully qualified class name
     * @return object
     * @throws \p810\Container\UnresolvableArgumentException if one of the class's constructor's arguments could not be resolved
     */
    public function resolve(string $className): object
    {
        $entry = $this->entry($className);
        
        $reflector = new ReflectionClass($entry->getClassName());
        $constructor = $reflector->getConstructor();

        if (! $constructor) {
            return $reflector->newInstance();
        }

        $arguments = $this->getConstructorArguments($constructor->getParameters(), $constructor->getDocComment(), $entry);

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * Iterates over the parameters in a class's constructor and attempts to resolve them so the class can be
     * instantiated by the Reflection API
     * 
     * @param \ReflectionParameter[] $parameters A list of \ReflectionParameter objects from a class's constructor
     * @param null|string            $docblock   The constructor's docblock, if applicable
     * @param \p810\Container\Entry  $entry      The \p810\Container\Entry object representing the class being instantiated
     * @return mixed[]
     * @throws \p810\Container\UnresolvableArgumentException if a parameter in a class's constructor could not be instantiated
     */
    protected function getConstructorArguments(array $parameters, ?string $docblock, Entry $entry): array
    {
        $dependencies = [];

        foreach ($parameters as $key => $parameter) {
            $default = $entry->getParam($parameter->getName());

            if (! $default instanceof UnsetDefaultParam) {
                $dependencies[$key] = $default;
                
                continue;
            }

            $instance = $this->resolveClassFromParameter($parameter) ??
              ($docblock ? $this->resolveClassFromDocComment($parameter->getName(), $docblock) : null);

            if (! $instance) {
                $paramName = '$' . $parameter->getName();
                /** @psalm-suppress PossiblyNullReference */
                $className = $parameter->getDeclaringClass()->getName();

                throw new UnresolvableArgumentException("Failed to create $className: A constructor argument ($paramName) either could not be inferred or instantiated");
            }

            $dependencies[$key] = $instance;
        }

        return $dependencies;
    }

    /**
     * Attempts to resolve a class from the type hint of the given parameter, and returns
     * the instantiated object if successful. Otherwise returns null.
     * 
     * @param \ReflectionParameter $parameter
     * @return null|object
     */
    protected function resolveClassFromParameter(ReflectionParameter $parameter): ?object {
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
    protected function resolveClassFromDocComment(string $parameterName, string $comment): ?object {
        $pattern = sprintf('/\s*@param\s*([A-Za-z0-9\\\_]+)\s*\$%s/', $parameterName);
        $matched = preg_match($pattern, $comment, $matches);

        if (! $matched) {
            return null;
        }

        return $this->resolve($matches[1]);
    }
}
