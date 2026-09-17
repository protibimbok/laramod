<?php

namespace Laramod;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use Laramod\Contracts\ProvidesSeeders;

class ModuleSeeder
{
    public function __construct(protected Container $container, protected ModuleRegistry $modules) {}

    /**
     * Run the seeders of every module, or of the given module only.
     */
    public function runSeeders(?string $module = null): void
    {
        $runner = new class extends Seeder
        {
            public function run(): void {}
        };

        $runner->setContainer($this->container);

        Model::unguarded(fn () => $runner->call($this->seeders($module)));
    }

    /**
     * Get the seeders of every module in the order the modules are wired, or of the given module only.
     *
     * @return list<class-string<Seeder>>
     */
    public function seeders(?string $module = null): array
    {
        if ($module !== null && ! $this->modules->has($module)) {
            throw new InvalidArgumentException(sprintf('Module [%s] is not registered.', $module));
        }

        $modules = $this->modules->providing(ProvidesSeeders::class);

        if ($module !== null) {
            $modules = array_intersect_key($modules, [$module => true]);
        }

        return array_merge(...array_map(
            fn (ProvidesSeeders $module): array => $module->seeders(), array_values($modules),
        ));
    }
}
