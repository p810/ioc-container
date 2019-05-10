<?php

namespace p810\Container;

use ReflectionClass;
use ReflectionParameter;

class ReflectionContainer extends Container implements Resolver
{
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
     * @param \ReflectionParameter[] $parameters
     * @param null|string $docblock
     * @param \p810\Container\Entry $entry
     */
    protected function getConstructorArguments(array $parameters, ?string $docblock, Entry $entry)
    {
        $dependencies = [];

        foreach ($parameters as $key => $parameter) {
            $default = $entry->getParam($parameter->getName());
        
            // we use a class here because hypothetically, any other value (including falsey values
            // like null or 0) could be returned, so the only truly unique way to show that the user
            // hasn't set a default value for this param is by using our own class
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

                throw new UnresolvableDependencyException("Failed to create $className: A constructor argument ($paramName) either could not be inferred or instantiated");
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
