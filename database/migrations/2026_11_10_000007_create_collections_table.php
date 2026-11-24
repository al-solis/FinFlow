<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('collection_no', 50)->nullable(); // CR-20261110-0001 or series-based

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->date('collection_date');

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');

            // "Deposit to" - resolves to a chart_of_account via bank_accounts, same as ap_payments
            $table->unsignedBigInteger('bank_account_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');

            $table->string('reference_number', 100)->nullable(); // transfer / deposit ref
            $table->string('check_number', 50)->nullable();
            $table->string('check_bank', 100)->nullable();
            $table->date('check_date')->nullable();

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);

            $table->decimal('amount_received', 18, 2)->default(0); // cash actually received
            $table->unsignedBigInteger('cwt_tax_id')->nullable();   // creditable withholding tax master used
            $table->foreign('cwt_tax_id')->references('id')->on('tax_masters');
            $table->decimal('cwt_amount', 18, 2)->default(0);       // withheld by customer (Form 2307)
            $table->decimal('amount_applied', 18, 2)->default(0);
            $table->decimal('amount_unapplied', 18, 2)->default(0);

            // Where any unapplied cash is credited (e.g. Customer Deposits) - same UX as liquidation credit_account_id
            $table->unsignedBigInteger('advance_account_id')->nullable();
            $table->foreign('advance_account_id')->references('id')->on('chart_of_accounts');

            $table->enum('status', ['draft', 'posted', 'bounced', 'voided'])->default('draft');
            // 0=draft 1=pending 2=approved 3=rejected 4=returned
            $table->string('approval_status', 1)->default('0')->after('status');

            $table->timestamp('submitted_at')->nullable()->after('approval_status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            $table->foreign('submitted_by')->references('id')->on('users');

            $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->timestamp('posted_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('posted_by')->nullable()->after('posted_at');
            $table->foreign('posted_by')->references('id')->on('users');

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals');

            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->foreign('voided_by')->references('id')->on('users');
            $table->string('void_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['organization_id', 'collection_date']);
            $table->index(['organization_id', 'approval_status']);
        });

        Schema::create('collection_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices');
            $table->decimal('amount_applied', 18, 2)->default(0);
            $table->decimal('cwt_applied', 18, 2)->default(0);
            $table->timestamps();
        });

        // BIR Form 2307 register: certificates expected from / received from customers
        Schema::create('wht_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->foreign('collection_id')->references('id')->on('collections');
            $table->string('form_type', 10)->default('2307');
            $table->string('certificate_no', 50)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('atc_code', 10)->nullable();
            $table->decimal('income_payment', 18, 2)->default(0);
            $table->decimal('tax_withheld', 18, 2)->default(0);
            $table->date('certificate_date')->nullable();
            $table->date('received_date')->nullable();
            $table->enum('status', ['expected', 'received', 'rejected'])->default('expected');
            $table->string('attachment_path')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wht_certificates');
        Schema::dropIfExists('collection_applications');
        Schema::dropIfExists('collections');
    }
};
