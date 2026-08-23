<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ap_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('payment_no')->unique(); // PV-20260820-0001

            $table->unsignedBigInteger('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors');

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');

            $table->date('payment_date');

            $table->unsignedBigInteger('bank_account_id'); // "Pay From" — resolves to a chart_of_account
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');

            $table->string('reference_number')->nullable(); // check/transfer number
            $table->date('check_date')->nullable();
            $table->string('disbursement_method', 30)->default('bank_transfer');

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->enum('status', ['posted', 'voided'])->default('posted');

            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_payments');
    }
};