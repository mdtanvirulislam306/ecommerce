<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Http\Requests\StoreCustomerSegmentRequest;
use Modules\Crm\Http\Requests\UpdateCustomerSegmentRequest;
use Modules\Crm\Models\CustomerSegment;
use Modules\Crm\Services\CustomerSegmentService;

class CustomerSegmentController extends Controller
{
    public function index(Request $request, CustomerSegmentService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();
        $isActive = $request->string('is_active')->toString();
        $sort = $request->string('sort')->toString() ?: 'sort_order';
        $direction = $request->string('direction')->toString() ?: 'asc';

        $filters = [
            'search' => $search ?: null,
            'is_active' => in_array($isActive, ['0', '1'], true) ? $isActive : null,
            'sort' => $sort,
            'direction' => $direction,
            'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
        ];

        return Inertia::render('Crm/Segments/Index', [
            'segments' => $service->listPaginated($filters),
            'customers' => $service->customerOptions(),
            'filters' => [
                'search' => $search,
                'is_active' => $filters['is_active'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $filters['per_page'],
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCustomerSegmentRequest $request, CustomerSegmentService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Customer segment created.');
    }

    public function update(UpdateCustomerSegmentRequest $request, CustomerSegment $customerSegment, CustomerSegmentService $service): RedirectResponse
    {
        $service->update($customerSegment, $request->validated());

        return back()->with('success', 'Customer segment updated.');
    }

    public function destroy(CustomerSegment $customerSegment, CustomerSegmentService $service): RedirectResponse
    {
        $service->delete($customerSegment);

        return back()->with('success', 'Customer segment removed.');
    }
}
