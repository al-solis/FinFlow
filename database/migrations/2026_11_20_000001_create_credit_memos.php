<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');

            $table->unsignedBigInteger('credit_memo_series_id')->nullable();
            $table->foreign('credit_memo_series_id')->references('id')->on('invoice_series');
            $table->string('credit_memo_no', 50)->nullable();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');

            // Optional link to a specific invoice being returned/credited
            $table->unsignedBigInteger('original_invoice_id')->nullable();
            $table->foreign('original_invoice_id')->references('id')->on('sales_invoices');

            // BIR buyer snapshot
            $table->string('bill_to_name', 150)->nullable();
            $table->string('bill_to_tin', 30)->nullable();
            $table->string('bill_to_address', 500)->nullable();

            $table->date('credit_date');
            $table->string('reason', 100);                  // 'return', 'overbill', 'discount', 'other'
            $table->text('remarks')->nullable();

            // Signed-positive; these reduce AR
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('vatable_sales', 18, 2)->default(0);
            $table->decimal('zero_rated_sales', 18, 2)->default(0);
            $table->decimal('vat_exempt_sales', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            // How the credit was used
            $table->decimal('amount_applied', 18, 2)->default(0);
            $table->decimal('amount_unapplied', 18, 2)->default(0);

            $table->unsignedTinyInteger('tax_option')->default(2);

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);

            // Approval (mirrors sales_invoice / collection)
            $table->string('status', 1)->default('0');
            $table->string('approval_status', 1)->default('0');

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

            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->foreign('voided_by')->references('id')->on('users');
            $table->string('void_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->index(['organization_id', 'credit_date']);
            $table->index(['customer_id', 'approval_status']);
            $table->unique(['organization_id', 'credit_memo_no']);
        });

        Schema::create('credit_memo_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_memo_id');
            $table->foreign('credit_memo_id')->references('id')->on('credit_memos')->cascadeOnDelete();

            $table->unsignedInteger('line_no');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')->references('id')->on('items');
            $table->string('description', 1000);

            // Contra-revenue account (Sales Returns, etc.)
            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');

            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_price', 18, 6);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->string('vat_class', 20);
            $table->decimal('taxable_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->unsignedBigInteger('uom_id')->nullable();
            $table->foreign('uom_id')->references('id')->on('unit_of_measures');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('credit_memo_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_memo_line_id');
            $table->foreign('credit_memo_line_id')->references('id')->on('credit_memo_lines')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->unsignedBigInteger('tax_id');
            $table->foreign('tax_id')->references('id')->on('tax_masters');
            $table->decimal('taxable_amount', 18, 2);
            $table->decimal('tax_amount', 18, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('credit_memo_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_memo_id');
            $table->foreign('credit_memo_id')->references('id')->on('credit_memos')->cascadeOnDelete();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices');
            $table->decimal('amount_applied', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memo_applications');
        Schema::dropIfExists('credit_memo_line_taxes');
        Schema::dropIfExists('credit_memo_lines');
        Schema::dropIfExists('credit_memos');
    }
};