<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        DB::table('tax_masters')->insert([
            [
                'code' => 'VAT12',
                'name' => '12% Input VAT',
                'tax_type_id' => 1,
                'tax_formula_id' => 1,
                'rate' => 12.00,
                'fixed_amount' => null,
                'gl_account_code' => null,
                'recoverable' => true,
                'priority' => 1,
                'effective_from' => now(),
                'effective_to' => null,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_by' => 1,
                'updated_at' => now(),
            ],
            [
                'code' => 'VAT12_OUT',
                'name' => '12% Output VAT',
                'tax_type_id' => 1,
                'tax_formula_id' => 1,
                'rate' => 12.00,
                'fixed_amount' => null,
                'gl_account_code' => null,
                'recoverable' => true,
                'priority' => 1,
                'effective_from' => now(),
                'effective_to' => null,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_by' => 1,
                'updated_at' => now(),
            ],
            [
                'code' => 'EWT2',
                'name' => 'Expanded Withholding Tax 2%',
                'tax_type_id' => 2,
                'tax_formula_id' => 3,
                'rate' => 2.00,
                'fixed_amount' => null,
                'gl_account_code' => null,
                'recoverable' => false,
                'priority' => 1,
                'effective_from' => now(),
                'effective_to' => null,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_by' => 1,
                'updated_at' => now(),
            ],
            [
                'code' => 'ENV50',
                'name' => 'Environmental Fee',
                'tax_type_id' => 4,
                'tax_formula_id' => 4,
                'rate' => 0,
                'fixed_amount' => 50.00,
                'gl_account_code' => null,
                'recoverable' => false,
                'priority' => 2,
                'effective_from' => now(),
                'effective_to' => null,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_by' => 1,
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_masters');
    }
};
