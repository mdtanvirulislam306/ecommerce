<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Marketing\Enums\ReferralStatus;
use Modules\Marketing\Models\Referral;

class ReferralService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Referral::query()
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('referrer_name', 'like', "%{$search}%")
                    ->orWhere('referrer_email', 'like', "%{$search}%")
                    ->orWhere('referee_email', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Referral $referral) => $this->format($referral));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return collect(ReferralStatus::cases())->map(fn (ReferralStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Referral
    {
        return Referral::query()->create([
            'code' => $data['code'] ?? Str::upper(Str::random(8)),
            'referrer_name' => $data['referrer_name'],
            'referrer_email' => $data['referrer_email'],
            'referee_email' => $data['referee_email'] ?? null,
            'status' => $data['status'] ?? ReferralStatus::Pending->value,
            'reward_amount' => $data['reward_amount'] ?? 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Referral $referral, array $data): Referral
    {
        $referral->update([
            'code' => $data['code'] ?? $referral->code,
            'referrer_name' => $data['referrer_name'] ?? $referral->referrer_name,
            'referrer_email' => $data['referrer_email'] ?? $referral->referrer_email,
            'referee_email' => $data['referee_email'] ?? $referral->referee_email,
            'status' => $data['status'] ?? $referral->status,
            'reward_amount' => $data['reward_amount'] ?? $referral->reward_amount,
        ]);

        return $referral->fresh();
    }

    public function delete(Referral $referral): void
    {
        $referral->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Referral $referral): array
    {
        return [
            'id' => $referral->id,
            'code' => $referral->code,
            'referrer_name' => $referral->referrer_name,
            'referrer_email' => $referral->referrer_email,
            'referee_email' => $referral->referee_email,
            'status' => $referral->status?->value,
            'status_label' => $referral->status?->label(),
            'reward_amount' => $referral->reward_amount,
            'created_at' => $referral->created_at?->toIso8601String(),
        ];
    }
}
