<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Laramod\Tests\TestCase;

class BoostGuidelineTest extends TestCase
{
    public function test_the_guideline_renders_the_way_boost_renders_it(): void
    {
        // Boost hands every guideline an "$assist" helper that knows how the application runs Artisan.
        $assist = new class
        {
            public function artisanCommand(string $command): string
            {
                return 'php artisan '.$command;
            }
        };

        $guideline = Blade::render(
            file_get_contents(dirname(__DIR__, 2).'/resources/boost/guidelines/core.blade.php'),
            ['assist' => $assist],
        );

        $this->assertStringStartsWith('# Laramod Modules', $guideline);
        $this->assertStringContainsString('`php artisan laramod:list --json`', $guideline);
        $this->assertStringContainsString('`ai_workflow`', $guideline);
        $this->assertStringContainsString('`php artisan make:model Post -mf --module=Blog`', $guideline);
    }
}
