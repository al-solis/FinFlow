<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gl_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gl_journal_id');
            $table->foreign('gl_journal_id')->references('id')->on('gl_journals')->cascadeOnDelete();
            $table->unsignedInteger('line_no');

            $table->unsignedBigInteger('gl_account_id'); // a specific posting-level chart_of_accounts row
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');

            $table->decimal('debit', 18, 6)->default(0);
            $table->decimal('credit', 18, 6)->default(0);
            $table->string('description')->nullable();

            // Traceability back to subledger (vendor for AP lines, rfd_detail for expense lines)
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->index(['gl_account_id']);
            $table->index(['subledger_type', 'subledger_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_journal_lines');
    }
};