<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');

            // General
            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('legal_name', 150)->nullable();
            $table->unsignedBigInteger('customer_category_id');
            $table->foreign('customer_category_id')->references('id')->on('customer_categories');
            $table->string('industry', 100)->nullable();

            // Tax
            $table->string('tax_id', 30)->nullable();           // TIN
            $table->string('tax_branch_code', 10)->nullable();
            $table->string('registration_no', 100)->nullable(); // SEC / DTI
            $table->enum('vat_classification', ['vatable', 'zero_rated', 'exempt', 'non_vat'])->default('vatable');
            $table->boolean('is_withholding_agent')->default(false); // withholds EWT and issues BIR Form 2307
            $table->unsignedBigInteger('default_cwt_tax_id')->nullable();
            $table->foreign('default_cwt_tax_id')->references('id')->on('tax_masters');
            $table->unsignedBigInteger('tax_group_id')->nullable();
            $table->foreign('tax_group_id')->references('id')->on('tax_groups');

            // Billing address
            $table->string('address1', 255)->nullable();
            $table->string('address2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('zip_code', 20)->nullable();

            // Contact
            $table->string('contact_person', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website', 100)->nullable();
            $table->text('contact_notes')->nullable();

            // Financial
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->foreign('payment_term_id')->references('id')->on('terms');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');

            // Mirrors vendors.ap_account_id / default_ap_chart_of_account_id
            $table->unsignedBigInteger('ar_account_id')->nullable();
            $table->foreign('ar_account_id')->references('id')->on('main_accounts');
            $table->unsignedBigInteger('default_ar_chart_of_account_id')->nullable();
            $table->foreign('default_ar_chart_of_account_id', 'customers_default_ar_coa_fk')
                ->references('id')->on('chart_of_accounts');
            $table->unsignedBigInteger('default_sales_discount_account_id')->nullable();
            $table->foreign('default_sales_discount_account_id')
                ->references('id')->on('chart_of_accounts');


            $table->decimal('credit_limit', 18, 2)->default(0); // 0 = no limit
            $table->boolean('credit_hold')->default(false);

            // Status
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
