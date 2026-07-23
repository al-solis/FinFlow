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
        Schema::create('tax_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('type', ['Percentage', 'FixedAmount', 'Formula'])->default('Percentage');
            $table->enum('basis', ['Gross', 'Net', 'Taxable', 'VAT'])->default('Gross');
            $table->enum('operation', ['Add', 'Deduct'])->default('Add');
            $table->string('expression', 255)->nullable();
            $table->enum('rounding', ['Round', 'Floor', 'Ceiling'])->default('Round');
            $table->integer('decimal_places')->default(2);
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('tax_formulas')->insert([
            [
                'code' => 'VAT_EXCLUSIVE',
                'name' => 'VAT Exclusive',
                'type' => 'Percentage',
                'basis' => 'Gross',
                'operation' => 'Add',
                'expression' => null,
                'rounding' => 'Round',
                'decimal_places' => 2,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'code' => 'VAT_INCLUSIVE',
                'name' => 'VAT Inclusive',
                'type' => 'Formula',
                'basis' => 'Gross',
                'operation' => 'Add',
                'expression' => 'Gross * Rate / (100 + Rate)',
                'rounding' => 'Round',
                'decimal_places' => 2,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'code' => 'EWT',
                'name' => 'Expanded Withholding Tax',
                'type' => 'Percentage',
                'basis' => 'Gross',
                'operation' => 'Deduct',
                'expression' => null,
                'rounding' => 'Round',
                'decimal_places' => 2,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'code' => 'FIXED',
                'name' => 'Fixed Fee',
                'type' => 'FixedAmount',
                'basis' => 'Gross',
                'operation' => 'Add',
                'expression' => null,
                'rounding' => 'Round',
                'decimal_places' => 2,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'code' => 'COMPOUND',
                'name' => 'Compound Tax',
                'type' => 'Formula',
                'basis' => 'VAT',
                'operation' => 'Add',
                'expression' => '(Gross + PreviousTax) * Rate / 100',
                'rounding' => 'Round',
                'decimal_places' => 2,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_formulas');
    }
};
