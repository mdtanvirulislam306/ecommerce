<?php

namespace Modules\Sales\Services;

use App\Core\Support\Service;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesPayment;
use Modules\Sales\Models\SalesQuotation;
use Modules\Sales\Models\SalesReturn;

class SalesReportService extends Service
{
    /**
     * @return array{
     *     quotations: array{total: int, draft: int, sent: int, accepted: int, expired: int, value: string},
     *     orders: array{total: int, confirmed: int, revenue: string},
     *     invoices: array{total: int, due: int, partial: int, paid: int, overdue: int, outstanding: string},
     *     payments: array{count: int, total: string},
     *     returns: array{count: int, confirmed: int, value: string},
     *     credit_notes: array{count: int, total: string}
     * }
     */
    public function overview(): array
    {
        $quotationCounts = SalesQuotation::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $invoiceCounts = SalesInvoice::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'quotations' => [
                'total' => (int) SalesQuotation::query()->count(),
                'draft' => (int) ($quotationCounts[QuotationStatus::Draft->value] ?? 0),
                'sent' => (int) ($quotationCounts[QuotationStatus::Sent->value] ?? 0),
                'accepted' => (int) ($quotationCounts[QuotationStatus::Accepted->value] ?? 0),
                'expired' => (int) ($quotationCounts[QuotationStatus::Expired->value] ?? 0),
                'value' => number_format((float) SalesQuotation::query()->sum('grand_total'), 2, '.', ''),
            ],
            'orders' => [
                'total' => (int) SalesOrder::query()->count(),
                'confirmed' => (int) SalesOrder::query()->where('status', SalesOrderStatus::Confirmed->value)->count(),
                'revenue' => number_format(
                    (float) SalesOrder::query()
                        ->where('status', SalesOrderStatus::Confirmed->value)
                        ->sum('grand_total'),
                    2,
                    '.',
                    '',
                ),
            ],
            'invoices' => [
                'total' => (int) SalesInvoice::query()->count(),
                'due' => (int) ($invoiceCounts[InvoiceStatus::Due->value] ?? 0),
                'partial' => (int) ($invoiceCounts[InvoiceStatus::Partial->value] ?? 0),
                'paid' => (int) ($invoiceCounts[InvoiceStatus::Paid->value] ?? 0),
                'overdue' => (int) ($invoiceCounts[InvoiceStatus::Overdue->value] ?? 0),
                'outstanding' => number_format((float) SalesInvoice::query()->sum('amount_due'), 2, '.', ''),
            ],
            'payments' => [
                'count' => (int) SalesPayment::query()->count(),
                'total' => number_format((float) SalesPayment::query()->sum('amount'), 2, '.', ''),
            ],
            'returns' => [
                'count' => (int) SalesReturn::query()->count(),
                'confirmed' => (int) SalesReturn::query()->where('status', SalesReturnStatus::Confirmed->value)->count(),
                'value' => number_format(
                    (float) SalesReturn::query()
                        ->where('status', SalesReturnStatus::Confirmed->value)
                        ->sum('grand_total'),
                    2,
                    '.',
                    '',
                ),
            ],
            'credit_notes' => [
                'count' => (int) SalesCreditNote::query()->count(),
                'total' => number_format((float) SalesCreditNote::query()->sum('amount'), 2, '.', ''),
            ],
        ];
    }
}
