<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Modules\Billing\Services\PlanService;
use Modules\Settings\Enums\StaffStatus;
use Modules\Settings\Models\Role;
use Modules\Settings\Notifications\StaffInvitationNotification;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private const TEAM_MANAGER_PERMISSIONS = [
        'settings.users.view',
        'settings.users.create',
        'settings.users.assign-roles',
        'settings.users.deactivate',
    ];

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));
        $this->owner = User::factory()->create(['name' => 'Shop Owner']);
        Notification::fake();
    }

    public function test_owner_invites_staff_who_get_an_emailed_link(): void
    {
        $cashier = $this->role('Cashier');

        $this->actingAs($this->owner)
            ->post(route('settings.users.store'), [
                'name' => 'Karim Hossain',
                'email' => ' Karim@Example.com ',
                'role_ids' => [$cashier->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $staff = User::query()->where('email', 'karim@example.com')->sole();
        $this->assertSame(app(TenantContext::class)->id(), $staff->tenant_id);
        $this->assertFalse($staff->is_owner);
        $this->assertSame(StaffStatus::Invited, $staff->staffStatus());
        $this->assertSame($this->owner->id, $staff->invited_by);
        $this->assertSame([$cashier->id], $staff->roles()->pluck('roles.id')->all());

        Notification::assertSentTo($staff, StaffInvitationNotification::class, function (StaffInvitationNotification $notification) use ($staff) {
            return $staff->invitation_token === hash('sha256', $notification->token)
                && str_contains($notification->toMail($staff)->actionUrl, route('invitation.show', $notification->token));
        });
    }

    public function test_invited_person_sets_a_password_and_is_signed_in(): void
    {
        $token = Str::random(48);
        $staff = User::factory()->invited($token)->create(['email' => 'karim@example.com']);

        $this->get(route('invitation.show', $token))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/AcceptInvitation')
                ->where('invitation.email', 'karim@example.com')
            );

        $this->post(route('invitation.accept', $token), [
            'name' => 'Karim Hossain',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('dashboard'));

        $staff->refresh();
        $this->assertAuthenticatedAs($staff, 'web');
        $this->assertSame(StaffStatus::Active, $staff->staffStatus());
        $this->assertSame('Karim Hossain', $staff->name);
        $this->assertNull($staff->invitation_token);
        $this->assertTrue(Hash::check('brand-new-pass', $staff->password));
    }

    public function test_invitation_link_works_only_once(): void
    {
        $token = Str::random(48);
        User::factory()->invited($token)->create();
        $accept = ['name' => 'Karim', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'];

        $this->post(route('invitation.accept', $token), $accept)->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        $this->post(route('invitation.accept', $token), $accept)->assertSessionHasErrors('password');
    }

    public function test_expired_invitation_cannot_be_used(): void
    {
        $token = Str::random(48);
        $staff = User::factory()->invited($token)->create(['invited_at' => now()->subDays(8)]);

        $this->get(route('invitation.show', $token))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invitation', null));

        $this->post(route('invitation.accept', $token), [
            'name' => 'Karim',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('password');

        $this->assertSame(StaffStatus::Invited, $staff->fresh()->staffStatus());
        $this->assertGuest('web');
    }

    public function test_invitation_from_another_shop_does_not_open_here(): void
    {
        $token = Str::random(48);
        $thisShop = app(TenantContext::class)->get();
        app(TenantContext::class)->set(Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]));
        User::factory()->invited($token)->create();
        app(TenantContext::class)->set($thisShop);

        $this->get(route('invitation.show', $token))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invitation', null));
    }

    public function test_email_already_on_the_team_is_rejected(): void
    {
        User::factory()->staff()->create(['email' => 'karim@example.com']);

        $this->actingAs($this->owner)
            ->post(route('settings.users.store'), [
                'name' => 'Karim',
                'email' => 'karim@example.com',
                'role_ids' => [$this->role('Cashier')->id],
            ])
            ->assertSessionHasErrors(['email' => 'Someone on your team already uses this email.']);
    }

    public function test_resending_an_invitation_replaces_the_old_link(): void
    {
        $oldToken = Str::random(48);
        $staff = User::factory()->invited($oldToken)->create();

        $this->actingAs($this->owner)
            ->post(route('settings.users.resend-invitation', $staff->id))
            ->assertRedirect();

        $this->assertNotSame(hash('sha256', $oldToken), $staff->fresh()->invitation_token);
        Notification::assertSentTo($staff, StaffInvitationNotification::class);
        $this->get(route('invitation.show', $oldToken))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invitation', null));
    }

    public function test_deactivated_staff_cannot_sign_in(): void
    {
        $staff = User::factory()->staff()->create(['email' => 'karim@example.com']);

        $this->actingAs($this->owner)->post(route('settings.users.deactivate', $staff->id))->assertRedirect();
        $this->post(route('logout'));

        $this->post(route('login'), ['email' => 'karim@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Your access to this shop has been turned off. Ask the shop owner to restore it.']);
        $this->assertGuest('web');
    }

    public function test_deactivated_staff_are_signed_out_on_their_next_request(): void
    {
        $staff = User::factory()->staff()->deactivated()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_reactivated_staff_can_sign_in_again(): void
    {
        $staff = User::factory()->staff()->deactivated()->create(['email' => 'karim@example.com']);

        $this->actingAs($this->owner)->post(route('settings.users.reactivate', $staff->id))->assertRedirect();
        $this->post(route('logout'));

        $this->post(route('login'), ['email' => 'karim@example.com', 'password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($staff->fresh(), 'web');
        $this->assertNotNull($staff->fresh()->last_login_at);
    }

    public function test_team_manager_cannot_deactivate_the_owner_or_themselves(): void
    {
        $manager = $this->staffWith(self::TEAM_MANAGER_PERMISSIONS);

        $this->actingAs($manager)->post(route('settings.users.deactivate', $this->owner->id))->assertNotFound();
        $this->actingAs($manager)->post(route('settings.users.deactivate', $manager->id))->assertForbidden();

        $this->assertFalse($this->owner->fresh()->isDeactivated());
        $this->assertFalse($manager->fresh()->isDeactivated());
    }

    public function test_staff_without_the_permission_cannot_deactivate_colleagues(): void
    {
        $viewer = $this->staffWith(['settings.users.view']);
        $colleague = User::factory()->staff()->create();

        $this->actingAs($viewer)->post(route('settings.users.deactivate', $colleague->id))->assertForbidden();

        $this->assertFalse($colleague->fresh()->isDeactivated());
    }

    public function test_team_manager_cannot_grant_more_access_than_they_have(): void
    {
        $manager = $this->staffWith(self::TEAM_MANAGER_PERMISSIONS);
        $admin = $this->role('Admin', [...self::TEAM_MANAGER_PERMISSIONS, 'settings.roles.update']);
        $viewer = $this->role('Viewer', ['settings.users.view']);
        $colleague = User::factory()->staff()->create();

        $this->actingAs($manager)
            ->put(route('settings.users.assign-roles', $colleague->id), ['role_ids' => [$admin->id]])
            ->assertSessionHasErrors('role_ids.0');
        $this->actingAs($manager)
            ->post(route('settings.users.store'), ['name' => 'New', 'email' => 'new@example.com', 'role_ids' => [$admin->id]])
            ->assertSessionHasErrors('role_ids.0');

        $this->actingAs($manager)
            ->put(route('settings.users.assign-roles', $colleague->id), ['role_ids' => [$viewer->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$viewer->id], $colleague->roles()->pluck('roles.id')->all());
    }

    public function test_only_unanswered_invitations_can_be_cancelled(): void
    {
        $invited = User::factory()->invited()->create();
        $joined = User::factory()->staff()->create();

        $this->actingAs($this->owner)->delete(route('settings.users.destroy', $invited->id))->assertRedirect();
        $this->actingAs($this->owner)->delete(route('settings.users.destroy', $joined->id))->assertStatus(422);

        $this->assertModelMissing($invited);
        $this->assertModelExists($joined);
    }

    public function test_team_page_counts_and_filters_by_status(): void
    {
        User::factory()->staff()->create();
        $invited = User::factory()->invited()->create();
        User::factory()->staff()->deactivated()->create();

        $this->actingAs($this->owner)
            ->get(route('settings.users.index', ['status' => 'invited']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Users/Index', false)
                ->where('summary', ['total' => 4, 'active' => 2, 'invited' => 1, 'deactivated' => 1])
                ->has('users.data', 1)
                ->where('users.data.0.id', $invited->id)
                ->where('users.data.0.status', 'invited')
            );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function role(string $name, array $permissions = []): Role
    {
        return Role::query()->create(['name' => $name, 'slug' => Str::slug($name), 'permissions' => $permissions]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function staffWith(array $permissions): User
    {
        $staff = User::factory()->staff()->create();
        $staff->roles()->attach($this->role('Role '.Str::random(6), $permissions));

        return $staff;
    }
}
