<?php

namespace Laramod\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\ModuleRegistry;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'laramod:list')]
class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laramod:list {--json : Output the modules as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the registered modules and what they provide';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $modules = array_values(array_map(fn (Module $module): array => [
            'name' => $module->name(),
            'class' => $module::class,
            'path' => $module->path(),
            'order' => $module instanceof Ordered ? $module->order() : 0,
            'capabilities' => $registry->capabilities($module),
            'ai_workflow' => $this->aiWorkflow($module),
            'publish_tags' => $this->publishTags($module),
        ], $registry->all()));

        if ($this->option('json')) {
            $this->line(json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($modules === []) {
            $this->components->info('No modules are registered. List them in bootstrap/modules.php.');

            return self::SUCCESS;
        }

        foreach ($modules as $module) {
            $this->newLine();
            $this->components->twoColumnDetail("<fg=cyan;options=bold>{$module['name']}</>", $module['class']);
            $this->components->twoColumnDetail('path', $this->relative($module['path']));
            $this->components->twoColumnDetail('order', (string) $module['order']);

            $this->components->twoColumnDetail('vendor:publish tags', implode(', ', $module['publish_tags']) ?: 'none');

            foreach ([...$module['capabilities'], 'ai-workflow' => $module['ai_workflow'] !== null] as $capability => $provided) {
                $this->line(sprintf(
                    '  <fg=%s>[%s]</> %s', $provided ? 'green' : 'red', $provided ? 'OK' : 'NO', $capability,
                ));
            }
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Get the entry point of the module's AI workflow, preferring the copy the application published.
     */
    protected function aiWorkflow(Module $module): ?string
    {
        $readmes = [
            $this->laravel->basePath('.ai/modules/'.$module->name().'/README.md'),
            $module->path().'/ai-workflow/README.md',
        ];

        return Arr::first($readmes, fn (string $readme): bool => is_file($readme));
    }

    /**
     * Get the "vendor:publish" tags that are registered for the module.
     *
     * @return list<string>
     */
    protected function publishTags(Module $module): array
    {
        return array_values(array_intersect(
            array_map(fn (string $kind): string => $module->name().'-'.$kind, ['views', 'config', 'lang', 'migrations', 'ai']),
            ServiceProvider::publishableGroups(),
        ));
    }

    /**
     * Get the path relative to the application's base path when it lives inside it.
     */
    protected function relative(string $path): string
    {
        return Str::chopStart($path, $this->laravel->basePath().DIRECTORY_SEPARATOR);
    }
}
