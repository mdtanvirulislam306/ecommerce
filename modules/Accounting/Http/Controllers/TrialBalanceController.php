<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Services\JournalService;

class TrialBalanceController extends Controller
{
    public function index(JournalService $journals): Response
    {
        $rows = $journals->trialBalance();
        $totalDebit = collect($rows)->sum(fn ($row) => (float) $row['debit']);
        $totalCredit = collect($rows)->sum(fn ($row) => (float) $row['credit']);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'rows' => $rows,
            'totals' => [
                'debit' => number_format($totalDebit, 4, '.', ''),
                'credit' => number_format($totalCredit, 4, '.', ''),
            ],
        ]);
    }
}
