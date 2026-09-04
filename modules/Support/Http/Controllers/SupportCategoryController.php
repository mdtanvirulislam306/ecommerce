<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Http\Requests\StoreSupportCategoryRequest;
use Modules\Support\Http\Requests\UpdateSupportCategoryRequest;
use Modules\Support\Models\SupportCategory;
use Modules\Support\Services\SupportCategoryService;

class SupportCategoryController extends Controller
{
    public function index(Request $request, SupportCategoryService $svc): Response
    {
        return Inertia::render('Support/Categories/Index', [
            'categories' => $svc->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'options' => method_exists($svc, 'formOptions') ? $svc->formOptions() : [],
        ]);
    }

    public function store(StoreSupportCategoryRequest $request, SupportCategoryService $svc): RedirectResponse
    {
        $svc->create($request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(UpdateSupportCategoryRequest $request, SupportCategory $supportCategory, SupportCategoryService $svc): RedirectResponse
    {
        $svc->update($supportCategory, $request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy(SupportCategory $supportCategory, SupportCategoryService $svc): RedirectResponse
    {
        $svc->delete($supportCategory);

        return back()->with('success', 'Deleted.');
    }
}
