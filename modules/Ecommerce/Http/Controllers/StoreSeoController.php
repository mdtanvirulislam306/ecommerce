<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ecommerce\Http\Requests\UpdateStoreSeoRequest;
use Modules\Ecommerce\Services\StoreSettingService;

class StoreSeoController extends Controller
{
    public function index(StoreSettingService $settings): Response
    {
        return Inertia::render('Ecommerce/Store/Seo', [
            'settings' => $settings->getMany(
                ['meta_title', 'meta_description', 'meta_keywords'],
                ['meta_title' => '', 'meta_description' => '', 'meta_keywords' => ''],
            ),
        ]);
    }

    public function update(UpdateStoreSeoRequest $request, StoreSettingService $settings): RedirectResponse
    {
        $settings->putMany($request->validated());

        return back()->with('success', 'SEO settings saved.');
    }
}
