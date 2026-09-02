<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cash_advance_liquidations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('cash_advance_id');
            $table->unsignedBigInteger('employee_id')->comment('User submitting the liquidation');
            $table->date('liquidation_date');
            $table->decimal('total_expenses', 15, 2);
            $table->unsignedBigInteger('credit_account_id')->nullable();

            $table->text('remarks')->nullable();

            // Status: 0=draft, 1=pending, 2=approved, 3=rejected, 4=returned
            $table->string('status', 1)->default('0');
            $table->string('approval_status', 1)->default('0');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('cash_advance_id')->references('id')->on('cash_advances')->onDelete('cascade');
            $table->foreign('credit_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Indexes
            $table->index(['cash_advance_id', 'employee_id']);
            $table->index(['employee_id', 'approval_status']);
            $table->index('organization_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_advance_liquidations');
    }
};