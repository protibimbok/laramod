<?php

namespace Laramod\Tests\Fixtures\Modules\Settings;

use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesConfig;

class SettingsModule implements Module, ProvidesConfig
{
    public function name(): string
    {
        return 'settings';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function config(): array
    {
        return ['modules.settings' => $this->path().'/config/settings.php'];
    }
}
