<?php

namespace Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Enums\PriceChangeAction;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Services\PriceHistoryService;

class PriceHistoryController extends Controller
{
    public function index(Request $request, PriceHistoryService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $priceListId = $request->filled('price_list_id') ? $request->integer('price_list_id') : null;

        $action = $request->filled('action')
            ? PriceChangeAction::tryFrom($request->string('action')->toString())
            : null;

        return Inertia::render('Commerce/PriceHistory/Index', [
            'history' => $service->listPaginated(
                search: $search ?: null,
                priceListId: $priceListId,
                action: $action,
                perPage: $perPage,
            ),
            'priceLists' => PriceList::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'actions' => collect(PriceChangeAction::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'filters' => [
                'search' => $search,
                'price_list_id' => $priceListId,
                'action' => $action?->value,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }
}
