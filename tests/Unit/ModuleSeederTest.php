<?php

namespace Laramod\Tests\Unit;

use Illuminate\Container\Container;
use InvalidArgumentException;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\ModuleRegistry;
use Laramod\ModuleSeeder;
use PHPUnit\Framework\TestCase;

class ModuleSeederTest extends TestCase
{
    public function test_seeders_are_collected_in_the_order_the_modules_are_wired(): void
    {
        $seeder = $this->seeder([
            $this->module('shop', order: 10),
            $this->module('blog', order: 1),
        ]);

        $this->assertSame(['blog-a', 'blog-b', 'shop-a', 'shop-b'], $seeder->seeders());
    }

    public function test_seeders_can_be_limited_to_one_module(): void
    {
        $seeder = $this->seeder([$this->module('shop', order: 10), $this->module('blog', order: 1)]);

        $this->assertSame(['shop-a', 'shop-b'], $seeder->seeders('shop'));
    }

    public function test_a_module_without_seeders_has_none(): void
    {
        $plain = new class implements Module
        {
            public function name(): string
            {
                return 'plain';
            }

            public function path(): string
            {
                return '/modules/plain';
            }
        };

        $this->assertSame([], $this->seeder([$plain])->seeders('plain'));
        $this->assertSame([], $this->seeder([$plain])->seeders());
    }

    public function test_an_unknown_module_cannot_be_seeded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Module [shop] is not registered.');

        $this->seeder([])->seeders('shop');
    }

    /**
     * @param  list<Module>  $modules
     */
    protected function seeder(array $modules): ModuleSeeder
    {
        $container = new Container;

        return new ModuleSeeder($container, (new ModuleRegistry($container))->register($modules));
    }

    protected function module(string $name, int $order): Module
    {
        return new class($name, $order) implements Module, Ordered, ProvidesSeeders
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

            public function seeders(): array
            {
                return [$this->name.'-a', $this->name.'-b'];
            }
        };
    }
}
