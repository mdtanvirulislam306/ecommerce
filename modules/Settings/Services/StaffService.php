<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use App\Core\Tenant\TenantContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Settings\Notifications\StaffInvitationNotification;

class StaffService extends Service
{
    public const INVITATION_VALID_DAYS = 7;

    public function __construct(private readonly TenantContext $tenants) {}

    /**
     * Creates the staff login with an unusable password; the invitee picks their own from the emailed link.
     *
     * @param  array{name: string, email: string, role_ids: list<int>}  $data
     */
    public function invite(array $data, User $inviter): User
    {
        $token = Str::random(48);

        $staff = DB::transaction(function () use ($data, $inviter, $token) {
            $staff = User::query()->create([
                'tenant_id' => $this->tenants->id(),
                'is_owner' => false,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(64),
                'invited_by' => $inviter->id,
                'invited_at' => now(),
                'invitation_token' => hash('sha256', $token),
            ]);

            $staff->roles()->sync($data['role_ids']);

            return $staff;
        });

        $staff->notify(new StaffInvitationNotification($token, $inviter->name));

        return $staff;
    }

    /**
     * Issues a fresh link, so any earlier invitation email stops working.
     */
    public function resendInvitation(User $staff, User $inviter): void
    {
        $token = Str::random(48);

        $staff->forceFill([
            'invited_at' => now(),
            'invitation_token' => hash('sha256', $token),
        ])->save();

        $staff->notify(new StaffInvitationNotification($token, $inviter->name));
    }

    /**
     * Shop owners and the person making the change are never manageable, so nobody locks the shop out or edits their own access.
     */
    public function findManageable(int $userId, User $actor): User
    {
        $staff = User::query()
            ->inCurrentShop()
            ->whereNotNull('tenant_id')
            ->where('is_owner', false)
            ->where('is_platform_admin', false)
            ->findOrFail($userId);

        abort_if($staff->is($actor), 403, 'You cannot change your own access.');

        return $staff;
    }

    public function findByInvitationToken(string $token): ?User
    {
        return User::query()
            ->inCurrentShop()
            ->whereNotNull('tenant_id')
            ->where('invitation_token', hash('sha256', $token))
            ->whereNull('invitation_accepted_at')
            ->whereNull('deactivated_at')
            ->where('invited_at', '>=', now()->subDays(self::INVITATION_VALID_DAYS))
            ->first();
    }

    public function acceptInvitation(User $staff, string $name, string $password): void
    {
        $staff->forceFill([
            'name' => $name,
            'password' => $password,
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
            'email_verified_at' => $staff->email_verified_at ?? now(),
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * Signs the person out everywhere: open sessions are dropped and "remember me" cookies stop matching.
     */
    public function deactivate(User $staff): void
    {
        $staff->forceFill([
            'deactivated_at' => now(),
            'invitation_token' => null,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $staff->id)->delete();
        }
    }

    public function reactivate(User $staff): void
    {
        $staff->forceFill(['deactivated_at' => null])->save();
    }

    public function cancelInvitation(User $staff): void
    {
        $staff->roles()->detach();
        $staff->delete();
    }

    /**
     * @return array{total: int, active: int, invited: int, deactivated: int}
     */
    public function summary(): array
    {
        $counts = User::query()
            ->inCurrentShop()
            ->whereNotNull('tenant_id')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when deactivated_at is not null then 1 else 0 end) as deactivated')
            ->selectRaw('sum(case when deactivated_at is null and invited_at is not null and invitation_accepted_at is null then 1 else 0 end) as invited')
            ->first();

        $total = (int) $counts->total;
        $deactivated = (int) $counts->deactivated;
        $invited = (int) $counts->invited;

        return [
            'total' => $total,
            'active' => $total - $deactivated - $invited,
            'invited' => $invited,
            'deactivated' => $deactivated,
        ];
    }
}
