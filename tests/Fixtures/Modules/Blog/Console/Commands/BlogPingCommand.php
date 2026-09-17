<?php

namespace Laramod\Tests\Fixtures\Modules\Blog\Console\Commands;

use Illuminate\Console\Command;

class BlogPingCommand extends Command
{
    protected $signature = 'blog:ping';

    protected $description = 'Ping the blog module';

    public function handle(): int
    {
        $this->line('pong');

        return self::SUCCESS;
    }
}
