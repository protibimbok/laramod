<?php

namespace Laramod\Tests\Fixtures\Modules\Blog\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class BlogSettingsSeeder extends Seeder
{
    public function run(): void
    {
        config()->push('blog.seeded', [static::class, Model::isUnguarded()]);
    }
}
