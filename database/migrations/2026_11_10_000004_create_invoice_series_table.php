<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * BIR requires sequential, gap-free document numbers within a registered series.
     * Numbers are issued from here at posting time (never at draft time).
     */
    public function up(): void
    {
        Schema::create('invoice_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->enum('document_type', ['sales_invoice', 'credit_memo', 'debit_memo', 'collection_receipt']);
            $table->string('code', 20);
            $table->string('prefix', 20)->nullable();
            $table->string('tax_branch_code', 10)->nullable();
            $table->unsignedBigInteger('series_start')->default(1);
            $table->unsignedBigInteger('series_end')->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->string('bir_authority_no', 60)->nullable(); // ATP / Permit to Use no.
            $table->date('authority_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->unique(['organization_id', 'document_type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_series');
    }
};
