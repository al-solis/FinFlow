<?php
// create_bank_reconciliation_lines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_reconciliation_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bank_reconciliation_import_id');
            $table->foreign('bank_reconciliation_import_id', 'brl_import_fk')
                ->references('id')->on('bank_reconciliation_imports')->cascadeOnDelete();

            $table->unsignedInteger('line_no');
            $table->date('transaction_date');

            // Signed: positive = deposit/credit to bank, negative = withdrawal/charge
            $table->decimal('amount', 18, 2);

            $table->string('payee')->nullable();
            $table->string('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('check_number', 50)->nullable();

            $table->boolean('accepted')->default(true);

            // Offsetting GL account chosen at review time (bank side is implied by bank_account)
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');

            $table->enum('status', ['pending', 'posted', 'skipped'])->default('pending');

            $table->unsignedBigInteger('gl_journal_id')->nullable();
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals');

            $table->timestamps();

            $table->index(['bank_reconciliation_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_lines');
    }
};
