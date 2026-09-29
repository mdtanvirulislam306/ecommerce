<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\ListPlatformUsersRequest;
use Modules\Platform\Services\PlatformUserService;

class UserController extends Controller
{
    public function index(ListPlatformUsersRequest $request, PlatformUserService $users): Response
    {
        $filters = $request->filters();

        return Inertia::render('Platform/Users/Index', [
            'users' => $users->listPaginated($filters),
            'filters' => $filters,
            'summary' => $users->summary(),
            'shops' => $users->shopOptions(),
        ]);
    }
}
