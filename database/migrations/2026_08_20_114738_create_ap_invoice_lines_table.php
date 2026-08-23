<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ap_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ap_invoice_id');
            $table->foreign('ap_invoice_id')->references('id')->on('ap_invoices')->cascadeOnDelete();

            // Traces back to the originating RFD line, for audit
            $table->string('source_line_type')->nullable(); // App\Models\rfd_detail
            $table->unsignedBigInteger('source_line_id')->nullable();

            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');
            $table->string('description')->nullable();
            $table->decimal('taxable_amount', 18, 6)->default(0);
            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('amount', 18, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_invoice_lines');
    }
};