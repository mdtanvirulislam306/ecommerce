<?php

namespace Modules\Commerce\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Commerce\Models\Referral;

class ReferralService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Referral::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('referrer_name', 'like', "%{$search}%")
                        ->orWhere('referrer_email', 'like', "%{$search}%")
                        ->orWhere('referee_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Referral
    {
        return Referral::query()->create([
            'code' => $data['code'],
            'referrer_name' => $data['referrer_name'],
            'referrer_email' => $data['referrer_email'] ?? null,
            'referee_email' => $data['referee_email'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'reward_amount' => $data['reward_amount'] ?? 0,
        ]);
    }

    public function update(Referral $referral, array $data): Referral
    {
        $referral->update([
            'code' => $data['code'] ?? $referral->code,
            'referrer_name' => $data['referrer_name'] ?? $referral->referrer_name,
            'referrer_email' => array_key_exists('referrer_email', $data) ? $data['referrer_email'] : $referral->referrer_email,
            'referee_email' => array_key_exists('referee_email', $data) ? $data['referee_email'] : $referral->referee_email,
            'status' => $data['status'] ?? $referral->status,
            'reward_amount' => $data['reward_amount'] ?? $referral->reward_amount,
        ]);

        return $referral->fresh();
    }

    public function delete(Referral $referral): void
    {
        $referral->delete();
    }
}
