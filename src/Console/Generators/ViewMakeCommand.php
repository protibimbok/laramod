<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ViewMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class ViewMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Get the destination view path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        // The view generator builds its path from viewPath(), so the trait's class path does not apply.
        return parent::getPath($name);
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     */
    protected function handleTestCreation($path): bool
    {
        // The view generator writes its own test, so the trait's "make:test" call does not apply.
        return parent::handleTestCreation($path);
    }

    /**
     * Get the destination test case path.
     */
    protected function getTestPath(): string
    {
        if (! $module = $this->targetModule()) {
            return parent::getTestPath();
        }

        $name = Str::after($this->testClassFullyQualifiedName(), $this->moduleNamespace($module).'\\');

        return $module->path().'/'.str_replace('\\', '/', $name).'Test.php';
    }

    /**
     * Get the class fully-qualified name for the test.
     */
    protected function testClassFullyQualifiedName(): string
    {
        $name = parent::testClassFullyQualifiedName();

        return ($module = $this->targetModule()) ? $this->moduleNamespace($module).'\\'.$name : $name;
    }

    /**
     * Get the view name for the test.
     */
    protected function testViewName(): string
    {
        $view = parent::testViewName();

        return ($module = $this->targetModule()) ? $module->name().'::'.$view : $view;
    }
}
