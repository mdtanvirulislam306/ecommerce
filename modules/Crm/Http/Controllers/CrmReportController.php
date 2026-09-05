<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crm\Services\CrmReportService;

class CrmReportController extends Controller
{
    public function index(Request $request, CrmReportService $service): Response
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);

        return Inertia::render('Crm/Reports/Index', $service->overview([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function dateRange(Request $request): array
    {
        $from = $this->parseDate($request->string('date_from')->toString());
        $to = $this->parseDate($request->string('date_to')->toString());

        if ($from === null && $to === null) {
            $to = now()->toDateString();
            $from = now()->subDays(89)->toDateString();
        }

        if ($from && $to && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
