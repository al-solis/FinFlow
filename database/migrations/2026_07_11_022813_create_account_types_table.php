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
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('description');
            $table->string('range');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('account_types')->insert([
            ['code' => 'ASSET', 'description' => 'Assets', 'range' => '1000-1999', 'created_by' => '1', 'created_at' => now()],
            ['code' => 'LIABILITY', 'description' => 'Liabilities', 'range' => '2000-2999', 'created_by' => '1', 'created_at' => now()],
            ['code' => 'EQUITY', 'description' => 'Equity', 'range' => '3000-3999', 'created_by' => '1', 'created_at' => now()],
            ['code' => 'REVENUE', 'description' => 'Revenue', 'range' => '4000-4999', 'created_by' => '1', 'created_at' => now()],
            ['code' => 'EXPENSE', 'description' => 'Expense', 'range' => '5000-6999', 'created_by' => '1', 'created_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
