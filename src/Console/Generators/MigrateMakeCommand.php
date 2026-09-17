<?php

namespace Laramod\Console\Generators;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Laramod\Console\Concerns\ResolvesModule;
use Laramod\Contracts\ProvidesMigrations;

class MigrateMakeCommand extends BaseCommand
{
    use ResolvesModule;

    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct($creator, $composer);

        $this->addModuleOption();
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     */
    protected function getMigrationPath(): string
    {
        if ($this->input->getOption('path') !== null || ! $module = $this->targetModule()) {
            return parent::getMigrationPath();
        }

        return $module instanceof ProvidesMigrations && $module->migrations() !== []
            ? $module->migrations()[0]
            : $module->path().'/Database/Migrations';
    }
}
