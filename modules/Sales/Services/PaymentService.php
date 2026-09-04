<?php

namespace Modules\Sales\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Enums\PaymentMethod;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesPayment;

class PaymentService extends Service
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SalesPayment::query()
            ->with('invoice:id,number,customer_name')
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($q) use ($search) {
                        $q->where('number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            }))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SalesPayment $payment) => [
                'id' => $payment->id,
                'number' => $payment->number,
                'invoice_id' => $payment->sales_invoice_id,
                'invoice_number' => $payment->invoice?->number,
                'customer_name' => $payment->invoice?->customer_name,
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method->value,
                'method_label' => $payment->method->label(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'notes' => $payment->notes,
                'created_at' => $payment->created_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  array{
     *     sales_invoice_id: int,
     *     amount: float|int|string,
     *     method?: string,
     *     paid_at?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, ?int $userId = null): SalesPayment
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoice = SalesInvoice::query()
                ->whereKey((int) $data['sales_invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > ((float) $invoice->amount_due) + 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds the remaining amount due.',
                ]);
            }

            $method = PaymentMethod::tryFrom($data['method'] ?? PaymentMethod::Cash->value)
                ?? PaymentMethod::Cash;

            $payment = SalesPayment::query()->create([
                'number' => $this->nextNumber(),
                'sales_invoice_id' => $invoice->id,
                'amount' => number_format($amount, 4, '.', ''),
                'currency' => $invoice->currency,
                'method' => $method,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->invoices->recalculateBalances($invoice);

            Log::info('sales.payment.recorded', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount,
            ]);

            return $payment;
        });
    }

    private function nextNumber(): string
    {
        $seq = SalesPayment::query()->lockForUpdate()->count() + 1;

        return 'PAY-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
