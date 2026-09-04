<?php

namespace Modules\Accounting\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\BankAccount;
use Modules\Accounting\Models\BankTransaction;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\CostCenter;
use Modules\Accounting\Models\FinancialClosing;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\FixedAsset;
use Modules\Accounting\Models\FixedAssetCategory;
use Modules\Accounting\Models\FixedAssetDepreciation;
use Modules\Accounting\Models\ProfitCenter;

class AccountingMasterService extends Service
{
    public function paginate(string $modelClass, ?string $search = null, int $perPage = 25, array $with = [], ?callable $through = null): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = $modelClass::query()->with($with);

        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), 'code')) {
                    $inner->orWhere('code', 'like', "%{$search}%");
                }
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        return $through ? $paginator->through($through) : $paginator;
    }

    public function createBankAccount(array $data): BankAccount
    {
        return BankAccount::query()->create($data);
    }

    public function updateBankAccount(BankAccount $account, array $data): BankAccount
    {
        $account->update($data);

        return $account->fresh();
    }

    public function deleteBankAccount(BankAccount $account): void
    {
        if ($account->transactions()->exists()) {
            throw ValidationException::withMessages(['account' => 'Cannot delete an account with transactions.']);
        }
        $account->delete();
    }

    public function createBankTransaction(array $data): BankTransaction
    {
        return BankTransaction::query()->create($data);
    }

    public function updateBankTransaction(BankTransaction $txn, array $data): BankTransaction
    {
        $txn->update($data);

        return $txn->fresh();
    }

    public function deleteBankTransaction(BankTransaction $txn): void
    {
        $txn->delete();
    }

    public function createNamed(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create($data);
    }

    public function updateNamed(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function deleteNamed(Model $model): void
    {
        $model->delete();
    }

    public function createBudget(array $data): Budget
    {
        return Budget::query()->create($data);
    }

    public function createFixedAsset(array $data): FixedAsset
    {
        $data['book_value'] = $data['book_value'] ?? $data['purchase_cost'] ?? 0;

        return FixedAsset::query()->create($data);
    }

    public function createDepreciation(array $data): FixedAssetDepreciation
    {
        return DB::transaction(function () use ($data) {
            $asset = FixedAsset::query()->whereKey($data['fixed_asset_id'])->lockForUpdate()->firstOrFail();
            $amount = (float) $data['amount'];

            $row = FixedAssetDepreciation::query()->create($data);
            $asset->update([
                'book_value' => number_format(max(0, (float) $asset->book_value - $amount), 4, '.', ''),
            ]);

            return $row;
        });
    }

    public function createFiscalYear(array $data): FiscalYear
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_current'] ?? false) {
                FiscalYear::query()->update(['is_current' => false]);
            }

            return FiscalYear::query()->create($data);
        });
    }

    public function closeFiscalYear(array $data, ?int $userId = null): FinancialClosing
    {
        return DB::transaction(function () use ($data, $userId) {
            $year = FiscalYear::query()->whereKey($data['fiscal_year_id'])->lockForUpdate()->firstOrFail();

            if ($year->is_closed) {
                throw ValidationException::withMessages(['fiscal_year_id' => 'Fiscal year is already closed.']);
            }

            $closing = FinancialClosing::query()->create([
                'fiscal_year_id' => $year->id,
                'closed_on' => $data['closed_on'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'closed_by' => $userId,
            ]);

            $year->update(['is_closed' => true, 'is_current' => false]);

            return $closing;
        });
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function bankAccountOptions(): array
    {
        return BankAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
            ->map(fn (BankAccount $a) => ['id' => $a->id, 'name' => $a->name, 'code' => $a->code])->all();
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function assetCategoryOptions(): array
    {
        return FixedAssetCategory::query()->orderBy('name')->get(['id', 'name', 'code'])
            ->map(fn (FixedAssetCategory $c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function fiscalYearOptions(): array
    {
        return FiscalYear::query()->orderByDesc('starts_on')->get(['id', 'name'])
            ->map(fn (FiscalYear $y) => ['id' => $y->id, 'name' => $y->name])->all();
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function assetOptions(): array
    {
        return FixedAsset::query()->orderBy('name')->get(['id', 'name', 'code'])
            ->map(fn (FixedAsset $a) => ['id' => $a->id, 'name' => $a->name, 'code' => $a->code])->all();
    }
}
