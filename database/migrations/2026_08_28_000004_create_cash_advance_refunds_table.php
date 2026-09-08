<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cash_advance_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('cash_advance_id')->nullable()->comment('Only for refunds from CA excess');
            $table->unsignedBigInteger('liquidation_id')->nullable()->after('cash_advance_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 15, 2);
            $table->text('purpose')->nullable();
            $table->unsignedBigInteger('gl_account_id')->nullable()->comment('GL account for the refund/reimbursement');

            // Type: 'refund' = employee to company, 'reimbursement' = company to employee
            $table->enum('type', ['refund', 'reimbursement'])->default('refund');

            // Status: 0=draft, 1=pending, 2=approved, 3=rejected, 4=returned, 5=paid
            $table->string('status', 1)->default('0');
            $table->string('approval_status', 1)->default('0');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('cash_advance_id')->references('id')->on('cash_advances');
            $table->foreign('liquidation_id')->references('id')->on('cash_advance_liquidations');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');
            $table->foreign('employee_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->foreign('paid_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Indexes
            $table->index(['employee_id', 'type', 'approval_status']);
            $table->index(['cash_advance_id', 'type']);
            $table->index('organization_id');
            $table->index('gl_account_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_advance_refunds');
    }
};