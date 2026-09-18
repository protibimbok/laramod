<?php

namespace Laramod\Tests\Unit;

use Illuminate\Container\Container;
use InvalidArgumentException;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Laramod\ModuleRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ModuleRegistryTest extends TestCase
{
    public function test_modules_are_sorted_by_order_with_negative_orders_last(): void
    {
        $registry = $this->registry()->register([
            $this->orderedModule('last', -1),
            $this->orderedModule('second', 10),
            $this->module('first-a'),
            $this->module('first-b'),
        ]);

        $this->assertSame(['first-a', 'first-b', 'second', 'last'], array_keys($registry->all()));
    }

    public function test_modules_registered_by_class_name_are_resolved_from_the_container(): void
    {
        $module = $this->module('blog');

        $container = new Container;
        $container->instance($module::class, $module);

        $registry = (new ModuleRegistry($container))->register($module::class);

        $this->assertSame($module, $registry->find('blog'));
        $this->assertTrue($registry->has('blog'));
        $this->assertFalse($registry->has('shop'));
    }

    public function test_modules_can_be_filtered_by_the_capability_they_provide(): void
    {
        $registry = $this->registry()->register([
            $this->module('plain'),
            $blog = new class implements Module, ProvidesMigrations, ProvidesViews
            {
                public function name(): string
                {
                    return 'blog';
                }

                public function path(): string
                {
                    return '/modules/blog';
                }

                public function migrations(): array
                {
                    return [$this->path().'/Database/Migrations'];
                }

                public function views(): string
                {
                    return $this->path().'/resources/views';
                }
            },
        ]);

        $this->assertSame(['blog' => $blog], $registry->providing(ProvidesViews::class));
        $this->assertSame(
            ['migrations', 'views'],
            array_keys(array_filter($registry->capabilities($blog))),
        );
    }

    public function test_vite_entries_are_named_by_their_path_from_the_project_root(): void
    {
        $container = new Container;
        $container->instance('path.base', '/var/www/app');

        $registry = new ModuleRegistry($container);

        $entries = fn (string $path, array $declared): array => $registry->viteEntries(
            new class($path, $declared) implements Module, ProvidesViteEntries
            {
                public function __construct(protected string $path, protected array $declared) {}

                public function name(): string
                {
                    return 'blog';
                }

                public function path(): string
                {
                    return $this->path;
                }

                public function viteEntries(): array
                {
                    return $this->declared;
                }
            }
        );

        $this->assertSame(
            ['Modules/Blog/resources/js/app.js', 'Modules/Blog/resources/css/app.css'],
            $entries('/var/www/app/Modules/Blog', ['resources/js/app.js', '/var/www/app/Modules/Blog/resources/css/app.css']),
        );
        $this->assertSame(
            ['vendor/acme/blog/resources/js/app.js'],
            $entries('/var/www/app/vendor/acme/blog', ['resources/js/app.js']),
        );
        $this->assertSame(
            ['C:/elsewhere/Blog/resources/js/app.js', 'C:/elsewhere/Blog/resources/js/admin.js'],
            $entries('C:\\elsewhere\\Blog', ['resources/js/app.js', 'C:\\elsewhere\\Blog\\resources\\js\\admin.js']),
        );
        $this->assertSame([], $registry->viteEntries($this->module('plain')));
    }

    public function test_a_path_is_relative_to_the_module_unless_it_is_absolute(): void
    {
        $registry = $this->registry();
        $module = $this->module('blog');

        $this->assertSame('/modules/blog/resources/views', $registry->path($module, 'resources/views'));
        $this->assertSame('/elsewhere/views', $registry->path($module, '/elsewhere/views'));
        $this->assertSame('C:\\elsewhere\\views', $registry->path($module, 'C:\\elsewhere\\views'));
        $this->assertSame('C:/elsewhere/views', $registry->path($module, 'C:/elsewhere/views'));
    }

    public function test_two_modules_cannot_share_a_name(): void
    {
        $registry = $this->registry()->register($this->module('blog'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Duplicate module [blog]');

        $registry->register($this->module('blog'));
    }

    public function test_a_registered_class_must_implement_the_module_contract(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->registry()->register(stdClass::class);
    }

    protected function registry(): ModuleRegistry
    {
        return new ModuleRegistry(new Container);
    }

    protected function module(string $name): Module
    {
        return new class($name) implements Module
        {
            public function __construct(protected string $name) {}

            public function name(): string
            {
                return $this->name;
            }

            public function path(): string
            {
                return '/modules/'.$this->name;
            }
        };
    }

    protected function orderedModule(string $name, int $order): Module
    {
        return new class($name, $order) implements Module, Ordered
        {
            public function __construct(protected string $name, protected int $order) {}

            public function name(): string
            {
                return $this->name;
            }

            public function path(): string
            {
                return '/modules/'.$this->name;
            }

            public function order(): int
            {
                return $this->order;
            }
        };
    }
}
