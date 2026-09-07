<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gl_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('journal_no')->unique();
            $table->date('journal_date');
            $table->string('source_module', 30);   // rfd, ap_payment, payment, refund, reimbursement, manual
            $table->string('journal_type', 30);    // approval_posting, disbursement, reversal, manual
            $table->string('reference_type');      // morph: App\Models\rfd_header, App\Models\ap_payment ...
            $table->unsignedBigInteger('reference_id');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 18, 6)->default(0);
            $table->decimal('total_credit', 18, 6)->default(0);
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->string('approval_status', 1)->default('0');
            $table->unsignedBigInteger('reversed_by_journal_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['organization_id', 'journal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_journals');
    }
};
