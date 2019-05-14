# ioc-container
> A dependency injection container that can autowire your objects with PHP's Reflection API

## Getting started
This package is available via Packagist.

```
$ composer require p810/ioc-container --no-dev
```

`p810\Container\Resolver` describes any class that contains functionality to automatically resolve (autowire) classes in your codebase. `p810\Container\Container` is an abstract class that may be used as a base for resolvers, providing storage functionality for instances of `p810\Container\Entry`, which is a value object used to describe classes in the container.

The default resolver shipped with this package is `p810\Container\ReflectionContainer`, which uses the [Reflection extension](https://www.php.net/manual/en/intro.reflection.php).

### Adding classes to the container
You can add classes to the container with `p810\Container\Container::set()`, providing a fully qualified class name. This will return an instance of `p810\Container\Entry` for your class:

```php
$container->set(Foo::class);
```

You can specify a custom factory for any given class by passing a callable value as the second argument. By default, `p810\Container\ReflectionContainer::resolve()` is used. This method will pass your class's dependencies into the container for automatic resolution, a process known as autowiring.

> :bulb: **Note:** Any classes whose constructor contains parameters that are not objects must explicitly define those parameters via the `p810\Container\Entry` instance returned by the container. This is covered below, under ["Specifying default values for parameters"](#specifying-default-values-for-parameters).

### Adding a singleton to the container
If you want an entry to only ever return one, specific object, either pass that object as the third argument to `p810\Container\Container::set()` or call `p810\Container\Container::singleton()`:

```php
$instance = new Foo(new Bar);

// this:
$container->set(Foo::class, $factory = null, $instance);

// is the same as this:
$container->singleton(Foo::class, $instance);
```

For your class to be resolved by the container, supplying an instance may be skipped by omitting the second argument, or setting it to `null`.

A callable may be passed as the third argument to use as the factory for instantiating your singleton. This is only used if you haven't already passed an instance.

A fourth parameter, `$resolveNow`, is an optional boolean that will tell the container either to delay instantiation until the object is requested, or to do it immediately. This is set to `false` by default for delayed instantiation.

```php
// this will trigger a call to ReflectionContainer::resolve() when Foo is requested:
$container->singleton(Foo::class);

// this will invoke the given anonymous function when Foo is requested:
$container->singleton(Foo::class, null, function () use ($bar): Foo {
    return new Foo($bar);
});

// this will invoke the given callback immediately:
$container->singleton(Foo::class, null, function (): Foo {
    return new Foo(new Bar);
}, true);
```

### Getting objects from the container
A class can be resolved from the container by calling `p810\Container\Container::get()`. The `Resolver` will attempt to automatically resolve any type hinted classes it finds in the class's constructor, either in the method signature or as an `@param` annotation in its docblock.

```php
class Foo {
    /**
     * @param Bar $bar
     * @param Bam $bam
     */
    function __construct(Bar $bar, $bam) {
        $this->bar = $bar;
        $this->bam = $bam;
    }
}

$foo = $container->get(Foo::class);
```

Default arguments may be passed to `p810\Container\Container::get()` after the name of the class being resolved. If an associative array is the only given argument after the class name, it will be treated as a dictionary of named parameters; otherwise values will be looked up numerically.

```php
// this:
$foo = $container->get(Foo::class, [
    'bam' => new Bam,
    'bar' => new Bar
]);

// is the same as this:
$foo = $container->get(Foo::class, new Bar, new Bam);
```

Argument values may also be bound to the `p810\Container\Entry` instance for a given class.

### Specifying default values for parameters
`p810\Container\Entry::param()` allows you to bind values to parameters of your constructor by name:

```php
class Bam {
    function __construct(string $message) {
        $this->message = $message;
    }
}

$entry = $container->set(Bam::class);

$entry->param('message', 'Hello world!');
```

You can also use the plural counterpart `p810\Container\Entry::params()` to set multiple parameters with one call, by passing an associative array:

```php
class Quux {
    function __construct(string $greeting, string $subject) {
        $this->message = \ucfirst($greeting) . ' ' . $subject . '!';
    }
}

$entry = $container->set(Quux::class);

$entry->params([
    'greeting' => 'hello',
    'subject'  => 'world'
]);
```

### Binding a specific class to an interface
The container can be configured to return an object of a specific class when a given interface is requested by means of `p810\Container\Container::bind()`. Give it the fully qualified class names of both the interface and its implementor:

```php
interface Baz {
    public function sayHello(string $subject): string;
}

class Bem implements Baz {
    public function sayHello(string $subject): string {
        return "Hello $subject!";
    }
}

$container->bind(Baz::class, Bem::class);

var_dump($container->get(Baz::class) instanceof Bem::class); //=> bool: true
```

> :bulb: **Note:** If the class you pass to `p810\Container\Container::bind()` has not already been registered to the container, it will be registered with the default settings. To customize a class's configuration you must register it before binding it to an interface.

## License
This package is free and open source under the [MIT License](LICENSE).
