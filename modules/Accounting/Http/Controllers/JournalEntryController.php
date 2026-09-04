<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalService;

class JournalEntryController extends Controller
{
    public function index(Request $request, JournalService $service): Response
    {
        $perPage = (int) $request->input('per_page', 25);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $service->listPaginated($search ?: null, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(ChartOfAccountsService $accounts): Response
    {
        return Inertia::render('Accounting/Journals/Create', [
            'accounts' => $accounts->options(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request, JournalService $service): RedirectResponse
    {
        $entry = $service->post($request->validated(), $request->user()->id);

        return redirect()
            ->route('accounting.journal-entries.show', $entry)
            ->with('success', 'Journal posted.');
    }

    public function show(JournalEntry $journalEntry, JournalService $service): Response
    {
        return Inertia::render('Accounting/Journals/Show', [
            'journal' => $service->formatDetail($journalEntry),
        ]);
    }
}
