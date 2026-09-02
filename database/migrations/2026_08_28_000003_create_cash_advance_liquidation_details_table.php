<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cash_advance_liquidation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('liquidation_id');
            $table->date('expense_date');
            $table->string('description', 255);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('reference', 50)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('liquidation_id')->references('id')->on('cash_advance_liquidations')->onDelete('cascade');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Indexes
            $table->index('liquidation_id');
            $table->index('gl_account_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_advance_liquidation_details');
    }
};