<?php

namespace Laramod\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesViteEntries;
use Laramod\ModuleRegistry;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'laramod:vite')]
class ViteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laramod:vite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Output what laramod-vite-plugin needs to know about the modules as JSON';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $modules = array_values(array_map(fn (Module $module): array => [
            'name' => $module->name(),
            'path' => $this->relative($module->path()),
            'entries' => array_map(
                $this->relative(...), $module instanceof ProvidesViteEntries ? $module->viteEntries() : [],
            ),
        ], $registry->all()));

        $this->line(json_encode(['modules' => $modules], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /**
     * Get the path the way Vite names it: relative to the project root, with forward slashes.
     */
    protected function relative(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return Str::chopStart($path, str_replace('\\', '/', $this->laravel->basePath()).'/');
    }
}
