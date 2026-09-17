<?php

namespace Laramod\Tests\Fixtures\Modules\Blog;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesGlobalMiddlewares;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Tests\Fixtures\Modules\Blog\Console\Commands\BlogPingCommand;
use Laramod\Tests\Fixtures\Modules\Blog\Database\Seeders\BlogSettingsSeeder;
use Laramod\Tests\Fixtures\Modules\Blog\Http\Middleware\AddBlogHeader;

class BlogModule implements Module, ProvidesApiRoutes, ProvidesCommands, ProvidesConfig, ProvidesGlobalMiddlewares, ProvidesMigrations, ProvidesRoutes, ProvidesSeeders, ProvidesTranslations, ProvidesViews
{
    public function name(): string
    {
        return 'blog';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require $this->path().'/routes/web.php';
    }

    public function apiRoutes(Router $router): void
    {
        require $this->path().'/routes/api.php';
    }

    public function globalMiddlewares(): array
    {
        return [AddBlogHeader::class];
    }

    public function migrations(): array
    {
        return [$this->path().'/Database/Migrations'];
    }

    public function seeders(): array
    {
        return [BlogSettingsSeeder::class];
    }

    public function commands(): array
    {
        return [BlogPingCommand::class];
    }

    public function views(): string
    {
        return $this->path().'/resources/views';
    }

    public function translations(): string
    {
        return $this->path().'/lang';
    }

    public function config(): array
    {
        return ['blog' => $this->path().'/config/blog.php'];
    }
}
