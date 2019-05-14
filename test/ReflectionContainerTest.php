<?php

namespace p810\Container\Test;

use PHPUnit\Framework\TestCase;
use p810\Container\ReflectionContainer;
use p810\Container\Test\Stubs\FooMockSingleton;
use p810\Container\Test\Stubs\FooMockDependent;
use p810\Container\Test\Stubs\FooMockInterface;
use p810\Container\Test\Stubs\FooMockDependency;
use p810\Container\Test\Stubs\BarMockDependency;
use p810\Container\Test\Stubs\BamMockDependency;
use p810\Container\Test\Stubs\QuuxMockDependency;
use p810\Container\Test\Stubs\FooMockImplementation;
use p810\Container\Test\Stubs\FooMockSingletonInterface;

use function md5;
use function microtime;

class ContainerTest extends TestCase
{
    /**
     * @var \p810\Container\ReflectionContainer
     */
    protected $container;

    public function setUp(): void
    {
        $this->container = new ReflectionContainer();
    }

    public function test_container_resolves_class_with_factory_method()
    {
        $this->container->set(FooMockDependency::class, function (): FooMockDependency {
            return new FooMockDependency();
        });

        $this->assertInstanceOf(FooMockDependency::class, $this->container->get(FooMockDependency::class));
    }

    public function test_container_resolves_class_with_reflection()
    {
        $this->container->set(BarMockDependency::class);

        $bar = $this->container->get(BarMockDependency::class);

        $this->assertInstanceOf(BarMockDependency::class, $bar);
        $this->assertInstanceOf(FooMockDependency::class, $bar->getFoo());
    }

    public function test_container_resolves_class_with_named_arguments()
    {
        $this->container->set(BarMockDependency::class);

        $foo = new FooMockDependency;
        $id = $foo->id = md5(microtime(true));

        $bar = $this->container->get(BarMockDependency::class, ['foo' => $foo]);

        $this->assertInstanceOf(FooMockDependency::class, $bar->getFoo());
        $this->assertEquals($id, $bar->getFoo()->id);
    }

    public function test_container_resolves_class_with_argument_list()
    {
        $this->container->set(BarMockDependency::class);

        $foo = new FooMockDependency;
        $id = $foo->id = md5(microtime(true));

        $bar = $this->container->get(BarMockDependency::class, $foo);

        $this->assertInstanceOf(FooMockDependency::class, $bar->getFoo());
        $this->assertEquals($id, $bar->getFoo()->id);
    }

    public function test_container_resolves_class_from_doccomment()
    {
        $this->container->set(BamMockDependency::class);

        $bam = $this->container->get(BamMockDependency::class);

        $this->assertInstanceOf(BamMockDependency::class, $bam);
        $this->assertInstanceOf(BarMockDependency::class, $bam->getBar());
        $this->assertInstanceOf(FooMockDependency::class, $bam->getFoo());
    }

    public function test_container_binds_implementation_to_interface()
    {
        $this->container->bind(FooMockInterface::class, FooMockImplementation::class);

        $this->container->set(FooMockDependent::class);

        $class = $this->container->get(FooMockDependent::class);

        $this->assertInstanceOf(FooMockInterface::class, $class->getFoo());
    }

    public function test_container_resolves_singleton()
    {
        $entry = $this->container->singleton(FooMockSingleton::class, null, null, true);

        $foo = $entry->getInstance();

        $this->assertTrue($foo === $this->container->get(FooMockSingleton::class));
        $this->assertInstanceOf(BarMockDependency::class, $foo->getBar());
    }

    public function test_container_returns_singleton_instance()
    {
        $entry = $this->container->singleton(FooMockSingleton::class, new class {});

        $foo = $entry->getInstance();

        $this->assertTrue($foo === $this->container->get(FooMockSingleton::class));
    }

    public function test_container_binds_singleton_to_interface()
    {
        $this->container->singleton(FooMockSingleton::class);

        $this->container->bind(FooMockSingletonInterface::class, FooMockSingleton::class);

        $x = $this->container->get(FooMockSingleton::class);
        $y = $this->container->get(FooMockSingletonInterface::class);

        $this->assertInstanceOf(FooMockSingleton::class, $y);
        $this->assertTrue($x === $y);
    }

    public function test_entry_has_default_argument()
    {
        $entry = $this->container->set(QuuxMockDependency::class);

        $entry->param('message', 'Hello world!');

        $instance = $this->container->get(QuuxMockDependency::class);

        $this->assertInstanceOf(QuuxMockDependency::class, $instance);
        $this->assertEquals('Hello world!', $instance->getMessage());
    }
}
