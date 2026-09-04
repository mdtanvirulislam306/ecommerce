<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\AttributeInputType;
use Modules\Catalog\Enums\AttributeType;
use Modules\Catalog\Http\Requests\StoreAttributeRequest;
use Modules\Catalog\Http\Requests\UpdateAttributeRequest;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Services\AttributeService;

class AttributeController extends Controller
{
    public function index(Request $request, AttributeService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $type = $request->filled('type')
            ? AttributeType::tryFrom($request->string('type')->toString())
            : null;

        return Inertia::render('Catalog/Attributes/Index', [
            'attributes' => $service->listPaginated($search ?: null, $type, $perPage),
            'filters' => [
                'search' => $search,
                'type' => $type?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
            'attributeTypes' => collect(AttributeType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'inputTypes' => collect(AttributeInputType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
        ]);
    }

    public function store(StoreAttributeRequest $request, AttributeService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()
            ->route('products.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute, AttributeService $service): RedirectResponse
    {
        $service->update($attribute, $request->validated());

        return redirect()
            ->route('products.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute, AttributeService $service): RedirectResponse
    {
        $service->delete($attribute);

        return redirect()
            ->route('products.attributes.index')
            ->with('success', 'Attribute removed.');
    }
}
