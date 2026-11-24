<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One table is both the sales document and the AR subledger (amount_paid / amount_due),
     * because one invoice = one customer. (AP needs rfd_headers + ap_invoices only because
     * one RFD can span several vendors.)
     */
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');

            // Assigned at final approval so rejected/returned drafts never burn a number.
            $table->unsignedBigInteger('invoice_series_id')->nullable();
            $table->foreign('invoice_series_id')->references('id')->on('invoice_series');
            $table->string('invoice_no', 50)->nullable();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');

            // Snapshot of buyer details as printed on the invoice
            $table->string('bill_to_name', 150)->nullable();
            $table->string('bill_to_tin', 30)->nullable();
            $table->string('bill_to_address', 500)->nullable();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->foreign('term_id')->references('id')->on('terms');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->unsignedTinyInteger('tax_option')->default(0)->comment('0=No Tax, 1=Tax Inclusive, 2=Tax Exclusive');

            // Optional link to quotation / sales order / contract (phase 4)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('customer_po_no', 50)->nullable();
            $table->date('billing_period_from')->nullable();
            $table->date('billing_period_to')->nullable();

            // BIR invoice breakdown
            $table->decimal('subtotal', 18, 2)->default(0);          // qty x price, before discount
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('vatable_sales', 18, 2)->default(0);
            $table->decimal('zero_rated_sales', 18, 2)->default(0);
            $table->decimal('vat_exempt_sales', 18, 2)->default(0);  // exempt + non-VAT
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);      // total amount due from customer

            // Subledger balances
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('cwt_applied', 18, 2)->default(0);       // tax withheld by customer (Form 2307)
            $table->decimal('credit_applied', 18, 2)->default(0);    // credit memos (phase 3)
            $table->decimal('debit_applied', 15, 2)->default(0);
            $table->decimal('amount_due', 18, 2)->default(0);

            // status / approval_status are written by ApprovalWorkflowService (submit/approve/return/reject)
            // exactly like cash_advances: 0=draft 1=pending 2=approved 3=rejected 4=returned
            $table->string('status', 1)->default('0');
            $table->string('approval_status', 1)->default('0');
            // Collection lifecycle is separate: an approved invoice is "unpaid" until settled
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->text('remarks')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users');
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->foreign('posted_by')->references('id')->on('users');

            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals');

            // Never delete an issued invoice - void it
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->foreign('voided_by')->references('id')->on('users');
            $table->string('void_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->index(['customer_id', 'payment_status']);
            $table->index(['organization_id', 'invoice_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
