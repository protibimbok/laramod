# Laramod

Explicit modules for Laravel.

A module is a class. What it provides is declared by the contracts that class implements, and modules are listed by hand in one file. There is no auto-discovery, no manifest, no cache to rebuild and no per-module `composer.json`.

```php
class BlogModule implements Module, ProvidesRoutes, ProvidesMigrations
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

    public function migrations(): array
    {
        return [$this->path().'/Database/Migrations'];
    }
}
```

## Requirements

- PHP 8.3+
- Laravel 13

## Installation

```bash
composer require protibimbok/laramod
php artisan laramod:init
```

`laramod:init` is safe to run again at any time. It:

- creates the `Modules/` directory and `bootstrap/modules.php`,
- publishes `config/laramod.php`,
- adds `"Modules\\": "Modules/"` to the PSR-4 autoloading in `composer.json` and runs `composer dump-autoload`,
- adds `Modules/*/Tests/Unit` and `Modules/*/Tests/Feature` to the test suites in `phpunit.xml`,
- tells you how to set up the Vite plugin when the application has a Vite config.

`composer.json` and `phpunit.xml` are edited as text, so the rest of the file stays as you wrote it. When the expected place is not found, the command prints the line to add by hand instead.

## Creating a module

```bash
php artisan make:module Blog
```

```
Modules/Blog/
├── BlogModule.php
├── ai-workflow/README.md
├── Http/Controllers/BlogController.php
├── Http/Controllers/Api/BlogController.php
├── Database/Migrations/
├── Tests/Feature/BlogModuleTest.php
├── config/blog.php
├── lang/en/messages.php
└── routes/{web,api}.php
```

The command also lists the module in `bootstrap/modules.php`:

```php
use Modules\Blog\BlogModule;

return [
    BlogModule::class,
];
```

| Option | Result |
| --- | --- |
| _(none)_ | Web and API routes, controllers, translations, config, migrations and a feature test |
| `--api` | API routes, a controller, config, migrations and a feature test |
| `--plain` | Only the module class, providing nothing yet |
| `--order=10` | Also implements `Ordered` with the given position |

Directories that hold classes are StudlyCase (`Http`, `Models`, `Database`, `Tests`), everything else is lowercase (`config`, `lang`, `resources`, `routes`, `ai-workflow`).

The stubs can be customised after `php artisan vendor:publish --tag=laramod-stubs`.

## Registering modules

`bootstrap/modules.php` returns the module classes. The order of the list is the order the modules are wired in. A module from a Composer package is added to the same list.

Modules can also be registered from the `register()` method of any service provider:

```php
use Laramod\Facades\Modules;

Modules::register([BlogModule::class]);
```

## What a module can provide

A module gains a capability by implementing its contract from `Laramod\Contracts`. Nothing is looked up on disk: a module without `ProvidesViews` has no views, whatever its directories contain.

| Contract | Method | Wired as |
| --- | --- | --- |
| `ProvidesRoutes` | `routes(Router $router): void` | Routes inside the `web` group of `config/laramod.php` |
| `ProvidesApiRoutes` | `apiRoutes(Router $router): void` | Routes inside the `api` group (prefix `api`) |
| `ProvidesGlobalMiddlewares` | `globalMiddlewares(): array` | Appended to the global middleware stack |
| `ProvidesMigrations` | `migrations(): array` | Directories that `migrate` also runs |
| `ProvidesSeeders` | `seeders(): array` | Seeders for the data the module needs, see [Seeders](#seeders) |
| `ProvidesCommands` | `commands(): array` | Artisan commands |
| `ProvidesViews` | `views(): string` | View and Blade component namespace, `blog::…` |
| `ProvidesTranslations` | `translations(): string` | Translation namespace, `blog::…`, and JSON translations |
| `ProvidesConfig` | `config(): array` | Config files, keyed by the key they are merged into |
| `ProvidesViteEntries` | `viteEntries(): array` | Source files Vite builds, see [Vite](#vite) |
| `Ordered` | `order(): int` | Lower is wired first, a negative order last, ties keep the registration order |

Views, translations and components use the module name as their namespace:

```php
view('blog::posts.index');
__('blog::messages.title');
```

```blade
<x-blog::alert />
```

Anonymous components live in `resources/views/components`, class components in the module's `View\Components` namespace.

### Routes

Module routes must point to controller actions, never closures, so `route:cache` keeps working. Once the routes are cached, the route methods of the modules are not called at all.

### Config

Generated modules merge their config under `modules.<name>`:

```php
public function config(): array
{
    return ['modules.blog' => $this->path().'/config/blog.php'];
}
```

```php
config('modules.blog.per_page');
```

The prefix keeps a module called `Auth`, `Cache` or `Mail` away from the framework's own config. A module is free to return another key.

### Seeders

`ProvidesSeeders` is for the data a module cannot work without. Demo and test data stay ordinary seeders that the application registers and calls itself.

Module seeders only run where you ask for them, for example in `DatabaseSeeder`:

```php
use Laramod\Facades\Seeder;

public function run(): void
{
    Seeder::runSeeders();
}
```

`Seeder::runSeeders()` runs the seeders of every module in the order the modules are wired, `Seeder::runSeeders('blog')` those of one module. Both are silent. For Laravel's usual console output, hand the classes to the seeder yourself with `$this->call(Seeder::seeders());`.

## Generators

Every generator of the framework accepts `--module` (the `make:*-table` commands do not: they publish the framework's own migrations, which no module owns):

```bash
php artisan make:model Post -mfs --module=Blog
php artisan make:controller PostController --module=Blog
php artisan make:test PostTest --module=Blog
```

Files are created inside the module with the module's namespace, including everything a command creates along the way (`make:model -mfs` keeps the migration, factory and seeder in the module). Without the option the commands behave exactly as they do in a stock application.

Laravel finds factories by naming convention, which does not reach into a module, so the generators link them explicitly: the model gets `#[UseFactory(PostFactory::class)]` and the factory gets `protected $model = Post::class;`.

Generated view names are namespaced (`view('blog::components.alert')`), and models, enums, traits, interfaces and scopes always go to `Models`, `Enums`, `Concerns`, `Contracts` and `Models/Scopes`.

A generator from another package supports the option once it uses the `Laramod\Console\Concerns\TargetsModule` trait, provided it extends Laravel's `GeneratorCommand`.

## Publishing

Installed modules are read-only, so what they provide can be copied into the application with the stock `vendor:publish`:

| Tag | Copied to |
| --- | --- |
| `blog-views` | `resources/views/vendor/blog` |
| `blog-config` | `config/modules/blog.php` |
| `blog-lang` | `lang/vendor/blog` |
| `blog-migrations` | `database/migrations` |
| `blog-ai` | `.ai/modules/blog` |

A module only has the tags for what it provides. Published views and translations override the module's, published config wins key by key, and published migrations keep their file names, so they never run twice.

## Vite

[`laramod-vite-plugin`](https://github.com/protibimbok/laramod-vite-plugin) builds the entries the modules declare, reloads the page when module views or routes change and adds an `@<module>` alias for every module.

```php
public function viteEntries(): array
{
    return [$this->path().'/resources/js/app.js'];
}
```

```blade
@vite('Modules/Blog/resources/js/app.js')
```

## AI workflow

A module carries its guidance for AI agents in its own `ai-workflow` directory: `README.md` is the entry point and `workflows/*.md` hold step-by-step guides. There are no `AGENTS.md` or `CLAUDE.md` files inside modules.

With [Laravel Boost](https://github.com/laravel/boost), select `protibimbok/laramod` when `boost:install` lists third-party guidelines. The application's agent is then told to run `laramod:list --json` and to read a module's workflow before changing the module or using its classes. An application can override the guidance of an installed module with `vendor:publish --tag=blog-ai`.

## Commands

| Command | |
| --- | --- |
| `laramod:init` | Prepare the application for modules |
| `laramod:list` | List the modules with what they provide, their publish tags and their AI workflow. `--json` for tools |
| `laramod:vite` | The JSON `laramod-vite-plugin` reads |
| `make:module {name}` | Create a module, `--api`, `--plain`, `--order=` |
| `make:* --module={name}` | Create a file inside a module |

## Configuration

`config/laramod.php`:

| Key | Default | |
| --- | --- | --- |
| `path` | `Modules` | Directory of the application's own modules, relative to the base path |
| `namespace` | `Modules` | Root namespace that directory is autoloaded under |
| `routes.web` | `['middleware' => ['web']]` | Group attributes of `ProvidesRoutes` |
| `routes.api` | `['prefix' => 'api', 'middleware' => ['api']]` | Group attributes of `ProvidesApiRoutes` |

## Testing

```bash
composer test
composer lint
```

## License

Laramod is open-sourced software licensed under the [MIT license](LICENSE).
