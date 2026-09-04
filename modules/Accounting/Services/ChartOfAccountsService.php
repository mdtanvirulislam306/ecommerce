<?php

namespace Modules\Accounting\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\LedgerAccount;

class ChartOfAccountsService extends Service
{
    /**
     * @return list<array{code: string, name: string, type: string, is_system: bool, sort_order: int, description: ?string}>
     */
    public function defaultChart(): array
    {
        return [
            ['code' => '1000', 'name' => 'Cash', 'type' => AccountType::Asset->value, 'is_system' => true, 'sort_order' => 10, 'description' => 'Cash on hand / till'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => AccountType::Asset->value, 'is_system' => true, 'sort_order' => 20, 'description' => 'Customer receivables'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => AccountType::Asset->value, 'is_system' => true, 'sort_order' => 30, 'description' => 'Stock asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => AccountType::Liability->value, 'is_system' => true, 'sort_order' => 40, 'description' => 'Supplier payables'],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => AccountType::Equity->value, 'is_system' => true, 'sort_order' => 50, 'description' => 'Opening equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => AccountType::Income->value, 'is_system' => true, 'sort_order' => 60, 'description' => 'Product sales'],
            ['code' => '5000', 'name' => 'Operating Expense', 'type' => AccountType::Expense->value, 'is_system' => true, 'sort_order' => 70, 'description' => 'General expenses'],
        ];
    }

    public function ensureDefaults(): void
    {
        foreach ($this->defaultChart() as $row) {
            LedgerAccount::query()->firstOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }
    }

    public function listPaginated(?string $search = null, ?AccountType $type = null, int $perPage = 50): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 50;

        return LedgerAccount::query()
            ->when($type, fn ($query, $type) => $query->where('type', $type->value))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (LedgerAccount $account) => $this->format($account));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tree(?AccountType $type = null): array
    {
        return LedgerAccount::query()
            ->when($type, fn ($query, $type) => $query->where('type', $type->value))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $account) => $this->format($account))
            ->all();
    }

    /**
     * @param  array{code: string, name: string, type: string, parent_id?: int|null, is_active?: bool, sort_order?: int, description?: string|null}  $data
     */
    public function create(array $data): LedgerAccount
    {
        return LedgerAccount::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LedgerAccount $account, array $data): LedgerAccount
    {
        if ($account->is_system) {
            $account->update([
                'name' => $data['name'] ?? $account->name,
                'is_active' => $data['is_active'] ?? $account->is_active,
                'description' => $data['description'] ?? $account->description,
                'sort_order' => $data['sort_order'] ?? $account->sort_order,
            ]);

            return $account->fresh();
        }

        $account->update([
            'code' => $data['code'] ?? $account->code,
            'name' => $data['name'],
            'type' => $data['type'] ?? $account->type->value,
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $data['is_active'] ?? $account->is_active,
            'sort_order' => $data['sort_order'] ?? $account->sort_order,
            'description' => $data['description'] ?? null,
        ]);

        return $account->fresh();
    }

    public function delete(LedgerAccount $account): void
    {
        if ($account->is_system) {
            throw ValidationException::withMessages([
                'account' => 'System accounts cannot be deleted.',
            ]);
        }

        if ($account->lines()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Cannot delete an account with journal lines. Deactivate it instead.',
            ]);
        }

        $account->delete();
    }

    public function findByCode(string $code): LedgerAccount
    {
        $account = LedgerAccount::query()->where('code', $code)->where('is_active', true)->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => "Ledger account {$code} is missing. Seed the chart of accounts.",
            ]);
        }

        return $account;
    }

    /**
     * @return list<array{id: int, code: string, name: string, type: string}>
     */
    public function options(): array
    {
        return LedgerAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(fn (LedgerAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(LedgerAccount $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type->value,
            'type_label' => $account->type->label(),
            'parent_id' => $account->parent_id,
            'is_system' => $account->is_system,
            'is_active' => $account->is_active,
            'sort_order' => $account->sort_order,
            'description' => $account->description,
        ];
    }
}
