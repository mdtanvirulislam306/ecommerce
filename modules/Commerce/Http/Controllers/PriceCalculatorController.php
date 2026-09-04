<?php

namespace Modules\Commerce\Http\Controllers;

use App\Core\Contracts\PriceResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commerce\Models\CustomerGroup;
use Modules\Commerce\Models\PriceList;
use Modules\Commerce\Services\PriceListService;

class PriceCalculatorController extends Controller
{
    public function index(Request $request, PriceListService $priceListService, PriceResolver $resolver): Response
    {
        $form = [
            'product_id' => $request->input('product_id', ''),
            'product_variant_id' => $request->input('product_variant_id', ''),
            'quantity' => $request->integer('quantity', 1),
            'customer_group_id' => $request->input('customer_group_id', ''),
            'price_list_id' => $request->input('price_list_id', ''),
        ];

        $preview = null;

        if ($request->filled('product_id')) {
            $preview = $resolver->resolve(
                productId: (int) $form['product_id'],
                quantity: max(1, (int) $form['quantity']),
                productVariantId: $form['product_variant_id'] ? (int) $form['product_variant_id'] : null,
                customerGroupId: $form['customer_group_id'] ? (int) $form['customer_group_id'] : null,
                priceListId: $form['price_list_id'] ? (int) $form['price_list_id'] : null,
            );
        }

        return Inertia::render('Commerce/PriceCalculator/Index', [
            'customerGroups' => CustomerGroup::query()
                ->where('is_active', true)
                ->with('priceList:id,name,code')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code', 'price_list_id']),
            'priceLists' => PriceList::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'code', 'currency']),
            'productOptions' => $priceListService->searchProducts(null, 50),
            'form' => $form,
            'preview' => $preview,
        ]);
    }
}
