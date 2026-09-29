<?php

namespace Tests\Feature;

use App\Core\Module\ModuleManager;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Services\PlanService;
use Modules\Crm\Enums\ActivityType;
use Modules\Crm\Enums\LeadStage;
use Modules\Crm\Models\CrmActivity;
use Modules\Crm\Models\Customer;
use Modules\Crm\Models\Lead;
use Modules\Crm\Models\LeadSource;
use Tests\TestCase;

class CrmModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlanService::class)->ensureDefaults(app(ModuleManager::class));

        $this->user = User::factory()->create();
    }

    public function test_crm_reports_funnel_uses_period_and_conversion_rates(): void
    {
        Lead::query()->create(['name' => 'New A', 'stage' => LeadStage::New]);
        Lead::query()->create(['name' => 'Contacted A', 'stage' => LeadStage::Contacted]);
        Lead::query()->create(['name' => 'Qualified A', 'stage' => LeadStage::Qualified]);
        Lead::query()->create(['name' => 'Won A', 'stage' => LeadStage::Won, 'converted_customer_id' => null]);
        Lead::query()->create(['name' => 'Lost A', 'stage' => LeadStage::Lost]);

        $old = Lead::query()->create(['name' => 'Old Won', 'stage' => LeadStage::Won]);
        $old->forceFill(['created_at' => now()->subDays(120)])->save();

        $this->actingAs($this->user)
            ->get(route('crm.reports', [
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.leads', 5)
                ->where('summary.won', 1)
                ->where('summary.lost', 1)
                ->where('summary.win_rate', 50)
                ->where('summary.open_pipeline', 3)
                ->has('funnel', 5)
                ->where('funnel.0.stage', 'new')
                ->where('funnel.0.reached', 4)
                ->where('funnel.0.in_stage', 1)
                ->where('funnel.4.stage', 'won')
                ->where('funnel.4.reached', 1)
                ->where('funnel.4.conversion_from_previous', 100)
                ->has('filters.date_from')
                ->has('by_source')
            );
    }

    public function test_crm_reports_defaults_to_recent_window(): void
    {
        $this->actingAs($this->user)
            ->get(route('crm.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.date_from', now()->subDays(89)->toDateString())
                ->where('filters.date_to', now()->toDateString())
                ->has('funnel')
                ->has('summary.win_rate')
            );
    }

    public function test_guests_are_redirected_from_crm_overview(): void
    {
        $this->get(route('crm.overview'))->assertRedirect(route('login', absolute: false));
    }

    public function test_lead_assignees_only_include_users_of_the_current_shop(): void
    {
        $otherShop = Tenant::query()->create(['name' => 'Other', 'slug' => 'other', 'status' => Tenant::STATUS_ACTIVE]);
        User::factory()->create(['name' => 'Other Shop Rep', 'tenant_id' => $otherShop->id]);

        $this->actingAs($this->user)->get(route('crm.leads.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('assignees', [['id' => $this->user->id, 'name' => $this->user->name]]));
    }

    public function test_overview_and_list_pages_render(): void
    {
        $this->actingAs($this->user)->get(route('crm.overview'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('stats.leads')->has('recentLeads'));

        $this->actingAs($this->user)->get(route('crm.leads.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('filters.sort')->has('assignees')->has('filters.scope')->has('filters.view'));

        $this->actingAs($this->user)->get(route('crm.customers.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('customers')->has('filters.sort'));

        $this->actingAs($this->user)->get(route('crm.activities.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('activities')->has('filters.status'));

        $this->actingAs($this->user)->get(route('crm.leads.all', ['view' => 'pipeline']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('leads')->has('stageCounts')->where('view', 'pipeline'));

        $this->actingAs($this->user)->get(route('crm.leads.sources'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('sources'));

        $this->actingAs($this->user)->get(route('crm.customers.segments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('segments'));
    }

    public function test_short_crm_list_urls_redirect_to_canonical_pages(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/crm/leads')
            ->assertRedirect(route('crm.leads.all', absolute: false));

        $this->actingAs($this->user)
            ->get(route('crm.leads.my'))
            ->assertRedirect(route('crm.leads.all', ['scope' => 'mine'], absolute: false));

        $this->actingAs($this->user)
            ->get(route('crm.leads.pipeline'))
            ->assertRedirect(route('crm.leads.all', ['view' => 'pipeline'], absolute: false));

        $this->actingAs($this->user)
            ->get('/admin/crm/customers')
            ->assertRedirect(route('crm.customers.all', absolute: false));

        $this->actingAs($this->user)
            ->get('/admin/crm/activities')
            ->assertRedirect(route('crm.activities.all', absolute: false));
    }

    public function test_leads_can_be_filtered_by_search_and_sorted_by_name(): void
    {
        Lead::query()->create(['name' => 'Zeta Corp', 'stage' => LeadStage::New, 'assigned_to' => $this->user->id]);
        Lead::query()->create(['name' => 'Alpha Traders', 'stage' => LeadStage::Qualified, 'assigned_to' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['search' => 'Alpha']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.name', 'Alpha Traders')
            );

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['sort' => 'name', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('leads.data.0.name', 'Alpha Traders')
                ->where('leads.data.1.name', 'Zeta Corp')
                ->where('filters.sort', 'name')
            );
    }

    public function test_leads_can_be_filtered_by_source_and_stage(): void
    {
        $source = LeadSource::query()->create([
            'name' => 'Website',
            'code' => 'WEB',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Lead::query()->create([
            'name' => 'Web Lead',
            'stage' => LeadStage::New,
            'source_id' => $source->id,
        ]);
        Lead::query()->create([
            'name' => 'Walk-in Lead',
            'stage' => LeadStage::Contacted,
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['source_id' => $source->id, 'stage' => 'new']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.name', 'Web Lead')
            );
    }

    public function test_customers_can_be_filtered_by_active_status(): void
    {
        Customer::query()->create(['name' => 'Active Shop', 'code' => 'CUS-A', 'is_active' => true]);
        Customer::query()->create(['name' => 'Closed Shop', 'code' => 'CUS-B', 'is_active' => false]);

        $this->actingAs($this->user)
            ->get(route('crm.customers.all', ['is_active' => '1']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Active Shop')
            );
    }

    public function test_customer_360_page_shows_linked_history_and_lifetime_value(): void
    {
        $customer = Customer::query()->create([
            'name' => '360 Buyer',
            'code' => 'CUS-360',
            'email' => 'buyer360@example.com',
            'is_active' => true,
        ]);

        Lead::query()->create([
            'name' => '360 Buyer Lead',
            'stage' => LeadStage::Won,
            'converted_customer_id' => $customer->id,
            'converted_at' => now(),
        ]);

        CrmActivity::query()->create([
            'type' => ActivityType::Call,
            'subject' => 'Check-in call',
            'due_at' => now()->addDay(),
            'customer_id' => $customer->id,
        ]);

        DB::table('sales_orders')->insert([
            'tenant_id' => $customer->tenant_id,
            'number' => 'SO-360-1',
            'status' => 'confirmed',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'currency' => 'BDT',
            'subtotal' => 1500,
            'grand_total' => 1500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customer.name', '360 Buyer')
                ->where('stats.lifetime_value', '1500.00')
                ->where('stats.sales_orders', 1)
                ->where('stats.leads', 1)
                ->where('stats.open_follow_ups', 1)
                ->has('activities', 1)
                ->where('activities.0.subject', 'Check-in call')
                ->has('leads', 1)
                ->has('sales_orders', 1)
                ->where('sales_orders.0.number', 'SO-360-1')
            );
    }

    public function test_guests_cannot_view_customer_360(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Private Buyer',
            'code' => 'CUS-PRIV',
            'is_active' => true,
        ]);

        $this->get(route('crm.customers.show', $customer))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_lead_convert_redirects_to_customer_360(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Convert Me',
            'email' => 'convertme@example.com',
            'stage' => LeadStage::Qualified,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('crm.leads.convert', $lead));

        $customer = Customer::query()->where('email', 'convertme@example.com')->first();
        $this->assertNotNull($customer);

        $response->assertRedirect(route('crm.customers.show', $customer, absolute: false));
    }

    public function test_lead_convert_with_create_order_opens_sales_order_form_prefilled(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Order Ready',
            'email' => 'orderready@example.com',
            'phone' => '01700009999',
            'stage' => LeadStage::Qualified,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('crm.leads.convert', $lead), ['create_order' => 1]);

        $customer = Customer::query()->where('email', 'orderready@example.com')->first();
        $this->assertNotNull($customer);

        $response->assertRedirect(route('sales.orders.create', ['customer_id' => $customer->id], absolute: false));

        $this->actingAs($this->user)
            ->get(route('sales.orders.create', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('prefillCustomer.id', $customer->id)
                ->where('prefillCustomer.name', 'Order Ready')
                ->where('prefillCustomer.email', 'orderready@example.com')
            );
    }

    public function test_leads_can_be_filtered_by_created_date_range(): void
    {
        $recent = Lead::query()->create(['name' => 'Recent Lead', 'stage' => LeadStage::New]);
        $old = Lead::query()->create(['name' => 'Old Lead', 'stage' => LeadStage::New]);
        $old->forceFill(['created_at' => now()->subDays(20)])->save();

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', [
                'date_from' => now()->subDays(3)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.name', 'Recent Lead')
                ->where('filters.date_from', now()->subDays(3)->toDateString())
            );

        $this->assertNotSame($recent->id, $old->id);
    }

    public function test_lead_export_csv_includes_only_filtered_rows(): void
    {
        Lead::query()->create(['name' => 'Alpha Traders', 'email' => 'alpha@example.com', 'stage' => LeadStage::New]);
        Lead::query()->create(['name' => 'Zeta Corp', 'email' => 'zeta@example.com', 'stage' => LeadStage::Qualified]);

        $response = $this->actingAs($this->user)
            ->get(route('crm.leads.export', ['format' => 'csv', 'search' => 'Alpha']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Alpha Traders', $csv);
        $this->assertStringContainsString('alpha@example.com', $csv);
        $this->assertStringNotContainsString('Zeta Corp', $csv);
    }

    public function test_lead_export_print_view_renders_filtered_table(): void
    {
        Lead::query()->create(['name' => 'Printable Lead', 'stage' => LeadStage::Contacted]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertSee('Leads')
            ->assertSee('Printable Lead');
    }

    public function test_guests_are_redirected_from_lead_export(): void
    {
        $this->get(route('crm.leads.export', ['format' => 'csv']))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_lead_export_rejects_unknown_format(): void
    {
        $this->actingAs($this->user)
            ->get(route('crm.leads.export', ['format' => 'xlsx']))
            ->assertSessionHasErrors('format');
    }

    public function test_pipeline_table_hides_lost_leads_and_filters_by_stage(): void
    {
        Lead::query()->create(['name' => 'Open Lead', 'stage' => LeadStage::Qualified]);
        Lead::query()->create(['name' => 'Lost Lead', 'stage' => LeadStage::Lost]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['view' => 'pipeline']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.name', 'Open Lead')
                ->where('stageCounts.qualified', 1)
                ->where('view', 'pipeline')
            );

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['view' => 'pipeline', 'stage' => 'qualified']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('filters.stage', 'qualified')
                ->where('filters.view', 'pipeline')
            );
    }

    public function test_mine_scope_limits_leads_to_current_user(): void
    {
        $other = User::factory()->create();

        Lead::query()->create([
            'name' => 'Mine Lead',
            'stage' => LeadStage::New,
            'assigned_to' => $this->user->id,
        ]);
        Lead::query()->create([
            'name' => 'Theirs Lead',
            'stage' => LeadStage::New,
            'assigned_to' => $other->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['scope' => 'mine']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.name', 'Mine Lead')
                ->where('scope', 'mine')
            );
    }

    public function test_lead_lists_expose_next_open_follow_up(): void
    {
        $lead = Lead::query()->create(['name' => 'Follow Lead', 'stage' => LeadStage::Qualified]);
        Lead::query()->create(['name' => 'Quiet Lead', 'stage' => LeadStage::New]);

        CrmActivity::query()->create([
            'type' => ActivityType::Note,
            'subject' => 'Completed note',
            'due_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
            'lead_id' => $lead->id,
        ]);
        CrmActivity::query()->create([
            'type' => ActivityType::Call,
            'subject' => 'Later call',
            'due_at' => now()->addDays(3),
            'lead_id' => $lead->id,
        ]);
        CrmActivity::query()->create([
            'type' => ActivityType::Task,
            'subject' => 'Soonest task',
            'due_at' => now()->addDay(),
            'lead_id' => $lead->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['search' => 'Follow']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.next_action.subject', 'Soonest task')
                ->where('leads.data.0.next_action.type', 'task')
                ->where('leads.data.0.next_action.is_overdue', false)
                ->has('activityTypeOptions')
            );

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['search' => 'Quiet']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.next_action', null)
            );
    }

    public function test_lead_next_action_marks_overdue_and_sorts_by_due_date(): void
    {
        $late = Lead::query()->create(['name' => 'Late Lead', 'stage' => LeadStage::Contacted]);
        $soon = Lead::query()->create(['name' => 'Soon Lead', 'stage' => LeadStage::Contacted]);

        CrmActivity::query()->create([
            'type' => ActivityType::Call,
            'subject' => 'Overdue call',
            'due_at' => now()->subDay(),
            'lead_id' => $late->id,
        ]);
        CrmActivity::query()->create([
            'type' => ActivityType::Call,
            'subject' => 'Upcoming call',
            'due_at' => now()->addDays(2),
            'lead_id' => $soon->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.leads.all', ['sort' => 'next_action', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('leads.data.0.name', 'Late Lead')
                ->where('leads.data.0.next_action.is_overdue', true)
                ->where('leads.data.1.name', 'Soon Lead')
                ->where('leads.data.1.next_action.is_overdue', false)
            );
    }

    public function test_activities_can_be_filtered_to_overdue(): void
    {
        $lead = Lead::query()->create(['name' => 'Follow Lead', 'stage' => LeadStage::New]);

        CrmActivity::query()->create([
            'type' => ActivityType::Call,
            'subject' => 'Late call',
            'due_at' => now()->subDay(),
            'lead_id' => $lead->id,
        ]);
        CrmActivity::query()->create([
            'type' => ActivityType::Note,
            'subject' => 'Future note',
            'due_at' => now()->addDay(),
            'lead_id' => $lead->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('crm.activities.all', ['status' => 'overdue']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.subject', 'Late call')
                ->where('activities.data.0.is_overdue', true)
            );
    }
}
