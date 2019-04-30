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
     * @param \ReflectionParameter[] $parameters
     * @param false|string           $docblock
     * @return object[]
     */
    protected function getDependenciesFromConstructor(array $parameters, $docblock): array {
        $dependencies = [];

        if (count($parameters) === 0) {
            return $dependencies;
        }

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

    protected function resolveClassFromDocComment(string $parameterName, string $comment): ?object {
        $pattern = sprintf('/\s*@param\s*([A-Za-z0-9\\\_]+)\s*\$%s/', $parameterName);
        $matched = preg_match($pattern, $comment, $matches);

        if (! $matched) {
            return null;
        }

        return $this->resolve($matches[1]);
    }

    public function bind(string $interfaceName, string $className): void {
        $this->bindings[$interfaceName] = $className;
    }

    protected function isBound(string $className): bool {
        return array_key_exists($className, $this->bindings);
    }

    protected function getBoundImplementation(string $className): object {
        return $this->resolve($this->bindings[$className]);
    }
}