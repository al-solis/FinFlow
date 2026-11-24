<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ar_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->unique();
            $table->foreign('organization_id')->references('id')->on('organizations');

            // =================================================================
            // Default accounts (fallbacks only).
            //
            // These four are the only accounts that have no per-record owner:
            //   - AR: customers.default_ar_chart_of_account_id, fallback here
            //   - Revenue: items.sales_gl_account_id, fallback here
            //   - Sales Discount: no per-record owner (header-level only)
            //   - Sales Returns: no per-record owner (credit memo posts here)
            //
            // Tax accounts (output VAT, input VAT, CWT) are NOT here. Each
            // tax_masters row owns its gl_account_id; posting reads that
            // column and refuses if it's null. No fallback.
            // =================================================================

            $table->unsignedBigInteger('default_ar_account_id')->nullable();
            $table->foreign('default_ar_account_id')->references('id')->on('chart_of_accounts');

            $table->unsignedBigInteger('default_revenue_account_id')->nullable();
            $table->foreign('default_revenue_account_id')->references('id')->on('chart_of_accounts');

            $table->unsignedBigInteger('default_sales_discount_account_id')->nullable();
            $table->foreign('default_sales_discount_account_id')->references('id')->on('chart_of_accounts');
            $table->boolean('record_sales_discount_in_gl')->default(false);

            $table->unsignedBigInteger('default_sales_return_account_id')->nullable();
            $table->foreign('default_sales_return_account_id')->references('id')->on('chart_of_accounts');

            // =================================================================
            // Defaults applied to new customer records. Pre-fill the customer
            // form so the AR clerk doesn't have to pick the same term /
            // category on every customer. Still editable per customer.
            // =================================================================

            $table->unsignedBigInteger('default_payment_term_id')->nullable();
            $table->foreign('default_payment_term_id')->references('id')->on('terms');

            $table->unsignedBigInteger('default_customer_category_id')->nullable();
            $table->foreign('default_customer_category_id')->references('id')->on('customer_categories');

            // =================================================================
            // Audit
            // =================================================================

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('sub_modules')->insert([
            'module_id' => 3,
            'code' => 'AR-SETTINGS',
            'name' => 'A/R Settings',
            'description' => 'Manage the A/R posting codes',
            'group' => 'Maintenance',
            'icon' => 'fa fa-gear',
            'img' => 'setting.png',
            'src' => null,
            'sequence' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('access_rights')->where('sub_module_id', function ($query) {
            $query->select('id')
                ->from('sub_modules')
                ->where('code', 'AR-SETTINGS');
        })->delete();
        DB::table('sub_modules')->where('code', 'AR-SETTINGS')->delete();
        Schema::dropIfExists('ar_settings');
    }
};