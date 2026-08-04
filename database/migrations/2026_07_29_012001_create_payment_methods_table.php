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
        Schema::create('payment_methods', function (Blueprint $table) {

            $table->id();

            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();

            // Accounting Behavior
            $table->boolean('requires_bank')->default(false);
            $table->boolean('requires_check')->default(false);
            $table->boolean('requires_reference_no')->default(false);
            $table->boolean('allow_partial_payment')->default(true);

            $table->integer('payment_channel')->unsigned()->comment('1 = Cash, 2 = Check, 3 = Bank Transfer, 4 = Credit Card, 5 = Debit Card, 6 = Online Payment, 7 = Digital Wallet, 8 = Other');

            $table->integer('status')->default(1);

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');

            $table->timestamps();

        });

        DB::table('payment_methods')->insert([

            [
                'code' => 'CASH',
                'name' => 'Cash',
                'requires_bank' => 0,
                'requires_check' => 0,
                'requires_reference_no' => 0,
                'allow_partial_payment' => 1,
                'payment_channel' => 1,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'CHECK',
                'name' => 'Check',
                'requires_bank' => 1,
                'requires_check' => 1,
                'requires_reference_no' => 1,
                'allow_partial_payment' => 1,
                'payment_channel' => 2,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'BANK',
                'name' => 'Bank Transfer',
                'requires_bank' => 1,
                'requires_check' => 0,
                'requires_reference_no' => 1,
                'allow_partial_payment' => 1,
                'payment_channel' => 3,
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
