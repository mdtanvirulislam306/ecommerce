<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('ssl_status')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'is_primary']);
        });

        Schema::create('tenant_module_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('module_code', 60);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'module_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->boolean('is_platform_admin')->default(false)->after('tenant_id');
        });

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (Throwable) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_unique');
                });
            } catch (Throwable) {
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['tenant_id', 'email']);
        });

        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                $table->text('payment_note')->nullable()->after('ends_at');
            });
        }

        if (Schema::hasTable('shop_settings') && ! Schema::hasColumn('shop_settings', 'tenant_id')) {
            Schema::table('shop_settings', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            });

            try {
                Schema::table('shop_settings', function (Blueprint $table) {
                    $table->dropUnique(['key']);
                });
            } catch (Throwable) {
                try {
                    Schema::table('shop_settings', function (Blueprint $table) {
                        $table->dropUnique('shop_settings_key_unique');
                    });
                } catch (Throwable) {
                }
            }

            Schema::table('shop_settings', function (Blueprint $table) {
                $table->unique(['tenant_id', 'key']);
            });
        }

        $tenantId = (int) DB::table('tenants')->insertGetId([
            'name' => 'Default Shop',
            'slug' => 'default',
            'status' => 'active',
            'notes' => 'Bootstrapped from existing single-tenant installation.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $host = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'));

        DB::table('tenant_domains')->insert([
            'tenant_id' => $tenantId,
            'domain' => $host,
            'is_primary' => true,
            'is_active' => true,
            'ssl_status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('store_domains')) {
            foreach (DB::table('store_domains')->get() as $row) {
                $domain = strtolower((string) $row->domain);
                if ($domain === $host) {
                    continue;
                }

                DB::table('tenant_domains')->updateOrInsert(
                    ['domain' => $domain],
                    [
                        'tenant_id' => $tenantId,
                        'is_primary' => (bool) $row->is_primary,
                        'is_active' => (bool) $row->is_active,
                        'ssl_status' => $row->ssl_status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update([
                'is_platform_admin' => true,
                'tenant_id' => null,
            ]);
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'tenant_id')) {
            DB::table('subscriptions')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        if (Schema::hasTable('shop_settings') && Schema::hasColumn('shop_settings', 'tenant_id')) {
            DB::table('shop_settings')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shop_settings') && Schema::hasColumn('shop_settings', 'tenant_id')) {
            Schema::table('shop_settings', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'key']);
                $table->dropConstrainedForeignId('tenant_id');
            });
            Schema::table('shop_settings', function (Blueprint $table) {
                $table->unique('key');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'tenant_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
                $table->dropColumn('payment_note');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('is_platform_admin');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::dropIfExists('tenant_module_overrides');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};
