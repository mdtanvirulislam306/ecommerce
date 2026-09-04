<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Http\Requests\StoreCreditNoteRequest;
use Modules\Sales\Services\CreditNoteService;
use Modules\Sales\Services\InvoiceService;

class CreditNoteController extends Controller
{
    public function index(Request $request, CreditNoteService $service, InvoiceService $invoices): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Sales/CreditNotes/Index', [
            'creditNotes' => $service->listPaginated($search ?: null, $perPage),
            'openInvoices' => $invoices->openInvoicesForSelect(),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function store(StoreCreditNoteRequest $request, CreditNoteService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('sales.credit-notes.index')
            ->with('success', 'Credit note issued.');
    }
}
