<?php

namespace Laramod\Tests\Fixtures\Modules\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function index(): View
    {
        return view('blog::index');
    }

    public function ping(): JsonResponse
    {
        return response()->json(['module' => 'blog']);
    }
}
