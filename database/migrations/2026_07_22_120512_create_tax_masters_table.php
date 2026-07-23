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
        Schema::create('tax_masters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->unsignedBigInteger('tax_type_id');
            $table->foreign('tax_type_id')->references('id')->on('tax_types');
            $table->unsignedBigInteger('tax_formula_id');
            $table->foreign('tax_formula_id')->references('id')->on('tax_formulas');
            $table->decimal('rate', 10, 2);
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->string('gl_account_code', 20)->nullable();
            // $table->unsignedBigInteger('gl_account_id');
            // $table->foreign('gl_account_id')->references('id')->on('gl_accounts');
            $table->boolean('recoverable')->default(false);
            $table->integer('priority')->default(1);
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::dropIfExists('tax_masters');
    }
};
