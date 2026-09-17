# Laramod Modules

This application is split into modules with `protibimbok/laramod`. A module is a class that implements `Laramod\Contracts\Module`. What it provides is declared by the `Laramod\Contracts\Provides*` contracts that class implements. Nothing is auto-discovered.

## Before Touching Or Calling A Module (IMPORTANT)
- Run `{{ $assist->artisanCommand('laramod:list --json') }}` to see every module with its `path`, `capabilities`, `publish_tags` and `ai_workflow`.
- When `ai_workflow` is not null you MUST read that file before changing the module or using its classes from elsewhere. It is the module's entry point for agents and lists the step-by-step guides in the `workflows` directory next to it. Follow the workflow that matches the task.
- A module without `ai_workflow` has no rules of its own. The conventions below still apply.
- Do not create `AGENTS.md`, `CLAUDE.md` or other agent files inside a module. A module's guidance lives only in its `ai-workflow` directory. Update it when you change how the module works or what it exposes to other code.

## Conventions
- Modules are listed explicitly in `bootstrap/modules.php`. The order of the list is the order they are wired in. Create a module with `{{ $assist->artisanCommand('make:module Blog') }}` (`--api`, `--plain`, `--order=`).
- Create files inside a module with the usual generators and the `--module` option, e.g. `{{ $assist->artisanCommand('make:model Post -mf --module=Blog') }}`. Do not create them in `app/` and move them.
- Class directories are StudlyCase (`Http`, `Models`, `Database/Migrations`, `Database/Factories`, `Database/Seeders`, `Tests/Feature`, `Tests/Unit`). Everything else is lowercase (`config`, `lang`, `resources`, `routes`, `ai-workflow`).
- Views and translations are namespaced by the module name: `view('blog::posts.index')`, `__('blog::messages.title')`, and `x-blog::alert` for Blade components.
- A module's config is read under the key its `config()` method declares, `modules.{name}` for generated modules: `config('modules.blog.per_page')`. Never use a top-level key that a framework config file already owns (`auth`, `cache`, `mail`, ...).
- Module routes must point to controller actions, never closures, so `route:cache` keeps working.
- A module gains a capability by implementing the matching contract on its module class, e.g. `ProvidesCommands::commands()`. New commands, global middleware, seeders and config files are registered there.
- Factories are linked explicitly: `#[UseFactory(PostFactory::class)]` on the model and `$model` on the factory. The generators do this for you.
- The seeders a module declares only run where the application calls `Laramod\Facades\Seeder::runSeeders()`.
- Module tests live in the module's `Tests` directory and run with the application's `Unit` and `Feature` suites.
- Modules installed through Composer are read-only. Customise them with `vendor:publish` and the tags from `laramod:list` (`blog-views`, `blog-config`, `blog-lang`, `blog-migrations`, `blog-ai`) instead of editing `vendor/`.
