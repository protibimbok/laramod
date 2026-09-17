<?php

namespace Laramod\Tests\Fixtures\Modules\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBlogHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Blog-Module', 'enabled');

        return $response;
    }
}
