<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Enums\PublicationStatus;
use Modules\Catalog\Http\Requests\UpdateCatalogSettingsRequest;
use Modules\Catalog\Models\Unit;
use Modules\Catalog\Services\CatalogSettingsService;

class CatalogSettingsController extends Controller
{
    public function index(CatalogSettingsService $settingsService): Response
    {
        return Inertia::render('Catalog/Settings/Index', [
            'settings' => $settingsService->forForm(),
            'units' => Unit::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'productStatuses' => collect(ProductStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'publicationStatuses' => collect(PublicationStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
        ]);
    }

    public function update(UpdateCatalogSettingsRequest $request, CatalogSettingsService $settingsService): RedirectResponse
    {
        $settingsService->update($request->validated());

        return back()->with('success', 'Catalog settings saved.');
    }
}
