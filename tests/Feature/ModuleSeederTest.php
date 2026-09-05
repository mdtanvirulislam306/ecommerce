<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModuleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_linked_demo_data_across_modules(): void
    {
        $this->seed();

        $this->assertDatabaseHas('products', [
            'sku' => 'BB-RICE-25',
            'status' => 'active',
            'publication_status' => 'published',
        ]);
        $this->assertDatabaseHas('products', ['sku' => 'BB-DRAFT-1', 'status' => 'draft']);
        $this->assertDatabaseHas('price_list_items', [
            'product_id' => DB::table('products')->where('sku', 'BB-TEA-500')->value('id'),
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'product_id' => DB::table('products')->where('sku', 'BB-DAL-1')->value('id'),
        ]);

        $this->assertDatabaseHas('customers', ['code' => 'CUS-00001', 'email' => 'walkin@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'buyer@acme.example']);
        $this->assertDatabaseHas('leads', [
            'email' => 'buyer@acme.example',
            'stage' => 'won',
        ]);
        $this->assertNotNull(DB::table('leads')->where('email', 'buyer@acme.example')->value('converted_customer_id'));

        $acmeId = DB::table('customers')->where('email', 'buyer@acme.example')->value('id');
        $walkInId = DB::table('customers')->where('code', 'CUS-00001')->value('id');

        $this->assertDatabaseHas('sales_orders', ['customer_id' => $acmeId]);
        $this->assertDatabaseHas('online_orders', [
            'customer_email' => 'guest@example.com',
        ]);
        $this->assertNotNull(DB::table('online_orders')->where('customer_email', 'guest@example.com')->value('customer_id'));
        $this->assertDatabaseHas('pos_registers', ['code' => 'REG-MAIN']);
        $this->assertDatabaseHas('pos_orders', ['customer_id' => $walkInId]);
        $this->assertTrue(DB::table('purchase_orders')->exists());
        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-DEMO']);

        $this->assertDatabaseHas('catalog_settings', [
            'default_product_status' => 'draft',
            'sku_prefix' => 'BB-',
        ]);
        $this->assertDatabaseHas('tax_rates', ['code' => 'VAT15']);
        $this->assertDatabaseHas('setting_values', ['key' => 'company_name', 'value' => 'Budget & Bazar']);
        $this->assertDatabaseHas('hrm_employees', ['code' => 'EMP-0001']);
        $this->assertDatabaseHas('support_tickets', ['requester_email' => 'buyer@acme.example']);
        $this->assertDatabaseHas('marketing_coupons', ['code' => 'WELCOME10']);
        $this->assertDatabaseHas('journal_entries', ['memo' => 'Opening cash and equity (seeder)']);
        $this->assertDatabaseHas('tasks', ['title' => 'Follow up Acme wholesale order']);
        $this->assertDatabaseHas('workflows', ['name' => 'Large sales order approval']);
        $this->assertDatabaseHas('notification_templates', ['name' => 'Order confirmed']);
        $this->assertDatabaseHas('documents', ['title' => 'Retail price list (sample)']);
    }

    public function test_database_seeder_can_run_twice_without_duplicating_catalog_or_customers(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, (int) DB::table('products')->where('sku', 'BB-RICE-25')->count());
        $this->assertSame(1, (int) DB::table('customers')->where('code', 'CUS-00001')->count());
        $this->assertSame(1, (int) DB::table('sales_orders')->count());
        $this->assertSame(1, (int) DB::table('leads')->where('email', 'buyer@acme.example')->count());
    }
}
