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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('branch', 100)->nullable();
            $table->string('account_name', 100);
            $table->string('account_number', 50);
            $table->unsignedBigInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->integer('account_type')->comment('1=Savings, 2=Current, 3=Time Deposit, 4=Money Market, 5=Other');
            $table->unsignedBigInteger('chart_of_account_id')->nullable();
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts');
            $table->integer('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
