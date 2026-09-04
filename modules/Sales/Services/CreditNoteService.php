<?php

namespace Modules\Sales\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\CreditNoteStatus;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;

class CreditNoteService extends Service
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesCreditNote::query()
            ->with('invoice:id,number,customer_name')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($q) use ($search) {
                        $q->where('number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesCreditNote $note) => [
                'id' => $note->id,
                'number' => $note->number,
                'status' => $note->status->value,
                'status_label' => $note->status->label(),
                'invoice_id' => $note->sales_invoice_id,
                'invoice_number' => $note->invoice?->number,
                'customer_name' => $note->invoice?->customer_name,
                'amount' => (string) $note->amount,
                'currency' => $note->currency,
                'reason' => $note->reason,
                'created_at' => $note->created_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  array{
     *     sales_invoice_id: int,
     *     amount: float|int|string,
     *     reason?: string|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): SalesCreditNote
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoice = SalesInvoice::query()
                ->whereKey((int) $data['sales_invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit amount must be greater than zero.',
                ]);
            }

            if ($amount > ((float) $invoice->amount_due) + 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit note cannot exceed the remaining amount due.',
                ]);
            }

            $note = SalesCreditNote::query()->create([
                'number' => $this->nextNumber(),
                'sales_invoice_id' => $invoice->id,
                'status' => CreditNoteStatus::Issued,
                'amount' => number_format($amount, 4, '.', ''),
                'currency' => $invoice->currency,
                'reason' => $data['reason'] ?? null,
                'created_by' => $userId,
            ]);

            $this->invoices->recalculateBalances($invoice);

            Log::info('sales.credit_note.issued', [
                'credit_note_id' => $note->id,
                'invoice_id' => $invoice->id,
                'amount' => $note->amount,
            ]);

            return $note;
        });
    }

    private function nextNumber(): string
    {
        $seq = SalesCreditNote::query()->lockForUpdate()->count() + 1;

        return 'CN-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
