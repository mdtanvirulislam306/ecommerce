<?php

namespace Tests;

use App\Core\Tenant\TenantContext;
use App\Models\Tenant;
use Database\Seeders\AssignDefaultTenantSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->bindDefaultTenant();
    }

    /**
     * Seeders run from the console with no shop bound, then AssignDefaultTenantSeeder hands orphan rows to
     * the Default Shop; seeding in tests mirrors that instead of running under the test's bound shop.
     *
     * @param  array<int, string>|string  $class
     */
    public function seed($class = 'Database\\Seeders\\DatabaseSeeder'): static
    {
        app(TenantContext::class)->clear();

        try {
            parent::seed($class);

            return parent::seed(AssignDefaultTenantSeeder::class);
        } finally {
            $this->bindDefaultTenant();
        }
    }

    /**
     * Local hosts resolve to the Default Shop on every request, so records arranged before
     * the first request must belong to it too or the tenant scope hides them.
     */
    protected function bindDefaultTenant(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $tenant = Tenant::query()->where('slug', 'default')->first();

        if ($tenant !== null) {
            app(TenantContext::class)->set($tenant);
        }
    }
}
