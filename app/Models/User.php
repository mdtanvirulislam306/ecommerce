<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Tenant\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Settings\Enums\StaffStatus;
use Modules\Settings\Models\Role;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'is_platform_admin', 'is_owner', 'invited_by', 'invited_at', 'invitation_token', 'invitation_accepted_at', 'deactivated_at', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'invitation_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string>|null */
    private ?array $permissionKeys = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'is_owner' => 'boolean',
            'invited_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function hasPendingInvitation(): bool
    {
        return $this->invited_at !== null && $this->invitation_accepted_at === null;
    }

    public function staffStatus(): StaffStatus
    {
        return match (true) {
            $this->isDeactivated() => StaffStatus::Deactivated,
            $this->hasPendingInvitation() => StaffStatus::Invited,
            default => StaffStatus::Active,
        };
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    /**
     * Platform admins and shop owners skip permission checks; everything else is granted through roles.
     */
    public function hasFullShopAccess(): bool
    {
        return $this->isPlatformAdmin() || (bool) $this->is_owner;
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissionKeys(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        return $this->permissionKeys ??= $this->roles()
            ->pluck('permissions')
            ->flatMap(fn (?array $permissions) => $permissions ?? [])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Staff of the shop bound to the current request. Users are not tenant-scoped globally
     * because platform admins (tenant_id null) must still authenticate on shop hosts.
     */
    public function scopeInCurrentShop(Builder $query): Builder
    {
        $tenantId = app(TenantContext::class)->id();

        return $tenantId === null ? $query : $query->where('users.tenant_id', $tenantId);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Shop users assigned the Sales Manager role. Users without that role stay on the owner dashboard.
     */
    public function isSalesManager(): bool
    {
        return $this->roles()->whereIn('slug', ['sales-manager', 'sales_manager'])->exists();
    }
}
