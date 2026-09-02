<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('invoice_no')->unique(); // internal AP reference, e.g. AP-20260820-0001

            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors');

            $table->string('source_type');   // App\Models\rfd_header (extendable to payment_request, etc.)
            $table->unsignedBigInteger('source_id');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);


            $table->decimal('gross_amount', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('net_amount', 18, 6)->default(0);   // amount owed to vendor
            $table->decimal('amount_paid', 18, 6)->default(0);
            $table->decimal('amount_due', 18, 6)->default(0);

            $table->enum('status', ['open', 'partially_paid', 'paid', 'cancelled'])->default('open');

            $table->unsignedBigInteger('gl_journal_id')->nullable(); // posting journal that created this liability
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals');

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_invoices');
    }
};