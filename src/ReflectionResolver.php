<?php

namespace p810\Container;

use ReflectionClass; 
use ReflectionParameter;

use function count;
use function sprintf;
use function preg_match;
use function array_key_exists;

class ReflectionResolver implements DependencyResolverInterface
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * {@inheritdoc}
     */
    public function resolve(string $className): object {
        if ($this->isBound($className)) {
            return $this->getBoundImplementation($className);
        }

        $reflector   = new ReflectionClass($className);
        $constructor = $reflector->getConstructor();

        if (! $constructor) {
            return $reflector->newInstance();
        }

        $dependencies = $this->getDependenciesFromConstructor(
            $constructor->getParameters(),
            $constructor->getDocComment()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Iterates over the parameters in a class's constructor and attempts to resolve
     * any classes that are hinted (docblock or native), and returns an array of the
     * objects that were instantiated
     * 
     * @param \ReflectionParameter[] $parameters A list of \ReflectionParameter objects
     * @param false|string           $docblock   The constructor's docblock, if applicable
     * @return object[]
     */
    protected function getDependenciesFromConstructor(array $parameters, $docblock): array {
        $dependencies = [];

        foreach ($parameters as $key => $parameter) {
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

    /**
     * {@inheritdoc}
     */
    public function bind(string $interfaceName, string $className): void {
        $this->bindings[$interfaceName] = $className;
    }

    /**
     * Returns a boolean indicating whether the given class is bound to another
     * 
     * @param string $className
     * @return bool
     */
    protected function isBound(string $className): bool {
        return array_key_exists($className, $this->bindings);
    }

    /**
     * Returns an instance of the class that's associated with the given class name
     * 
     * @param string $className
     * @return object
     */
    protected function getBoundImplementation(string $className): object {
        return $this->resolve($this->bindings[$className]);
    }
}