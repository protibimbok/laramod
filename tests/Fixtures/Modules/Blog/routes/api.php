<?php

use Illuminate\Routing\Router;
use Laramod\Tests\Fixtures\Modules\Blog\Http\Controllers\PostController;

/** @var Router $router */
$router->get('/blog/ping', [PostController::class, 'ping'])->name('blog.api.ping');
