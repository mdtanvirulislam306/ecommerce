<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 80)->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('opening_balance', 16, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('type', 20); // deposit | withdrawal | transfer
            $table->decimal('amount', 16, 4);
            $table->string('reference', 80)->nullable();
            $table->string('memo')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();

            $table->index(['bank_account_id', 'txn_date']);
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('profit_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('period', 20);
            $table->foreignId('ledger_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->decimal('amount', 16, 4)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fixed_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->decimal('useful_life_years', 8, 2)->default(5);
            $table->decimal('depreciation_rate', 8, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->foreignId('fixed_asset_category_id')->nullable()->constrained('fixed_asset_categories')->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 16, 4)->default(0);
            $table->decimal('book_value', 16, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('period_date');
            $table->decimal('amount', 16, 4);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->date('closed_on');
            $table->string('notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_closings');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('fixed_asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('fixed_asset_categories');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('profit_centers');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
