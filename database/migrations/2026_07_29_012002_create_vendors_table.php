<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');

            /*
            |--------------------------------------------------------------------------
            | General
            |--------------------------------------------------------------------------
            */

            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('legal_name', 150)->nullable();
            $table->unsignedBigInteger('vendor_category_id');
            $table->foreign('vendor_category_id')
                ->references('id')
                ->on('vendor_categories');

            $table->string('industry', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            $table->string('tax_id', 30)->nullable();
            $table->string('tax_branch_code', 10)->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->string('bir_rdo_code', 10)->nullable();

            $table->unsignedBigInteger('tax_group_id')->nullable();
            $table->foreign('tax_group_id')
                ->references('id')
                ->on('tax_groups');

            /*
            |--------------------------------------------------------------------------
            | Primary Address
            |--------------------------------------------------------------------------
            */
            $table->string('address1', 255)->nullable();
            $table->string('address2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('zip_code', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            $table->string('contact_person', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website', 100)->nullable();
            $table->text('contact_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');

            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->foreign('payment_term_id')
                ->references('id')
                ->on('terms');

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods');

            $table->unsignedBigInteger('ap_account_id')->nullable();
            $table->foreign('ap_account_id')
                ->references('id')
                ->on('main_accounts');

            $table->decimal('credit_limit', 18, 2)->default(0);
            /*
            |--------------------------------------------------------------------------
            | Purchasing
            |--------------------------------------------------------------------------
            */
            $table->boolean('requires_po')->default(true);
            $table->integer('lead_time')
                ->default(0)
                ->comment('Lead time in days');
            $table->boolean('preferred_vendor')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users');

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')
                ->references('id')
                ->on('users');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};