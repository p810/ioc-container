<?php

namespace p810\Container\Test;

use PHPUnit\Framework\TestCase;
use p810\Container\ReflectionResolver;
use p810\Container\Test\Stubs\FooMockDependency;
use p810\Container\Test\Stubs\BarMockDependency;
use p810\Container\Test\Stubs\BamMockDependency;

class ReflectionResolverTest extends TestCase
{
    /**
     * @var \p810\Container\ReflectionResolver
     */
    protected $resolver;

    public function setUp(): void
    {
        $this->resolver = new ReflectionResolver;
    }

    public function test_resolves_class_with_factory_method()
    {
        $this->assertInstanceOf(FooMockDependency::class, $this->resolver->resolve(FooMockDependency::class));
    }

    public function test_resolves_class_with_reflection()
    {
        $bar = $this->resolver->resolve(BarMockDependency::class);

        $this->assertInstanceOf(BarMockDependency::class, $bar);
        $this->assertInstanceOf(FooMockDependency::class, $bar->getFoo());
    }

    public function test_resolves_class_from_doccomment()
    {
        $bam = $this->resolver->resolve(BamMockDependency::class);

        $this->assertInstanceOf(BamMockDependency::class, $bam);
        $this->assertInstanceOf(BarMockDependency::class, $bam->getBar());
        $this->assertInstanceOf(FooMockDependency::class, $bam->getFoo());
    }
}
