<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->cascadeOnDelete();
            $table->integer('line_no');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')->references('id')->on('items');
            $table->text('description');
            $table->unsignedBigInteger('uom_id');
            $table->foreign('uom_id')->references('id')->on('unit_of_measures');

            // Revenue account credited for this line
            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');

            // Drives the VATable / zero-rated / exempt breakdown printed on the invoice
            $table->enum('vat_class', ['vatable', 'zero_rated', 'exempt', 'non_vat'])->default('vatable');

            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('unit_price', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);
            $table->decimal('taxable_amount', 18, 6)->default(0); // (qty x price) - discount
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('total_amount', 18, 6)->default(0);
            $table->string('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('sales_invoice_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_line_id');
            $table->foreign('sales_invoice_line_id')->references('id')->on('sales_invoice_lines')->cascadeOnDelete();
            $table->integer('line_no');
            $table->unsignedBigInteger('tax_id');
            $table->foreign('tax_id')->references('id')->on('tax_masters');
            $table->decimal('taxable_amount', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_line_taxes');
        Schema::dropIfExists('sales_invoice_lines');
    }
};
