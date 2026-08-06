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
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->unsignedBigInteger('account_type_id');
            $table->foreign('account_type_id')->references('id')->on('account_types');
            $table->string('description', 120);
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('account_categories')->insert([
            ['organization_id' => 1, 'account_type_id' => 1, 'description' => 'Current Assets', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 1, 'description' => 'Non-Current Assets', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 2, 'description' => 'Current Liabilities', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 2, 'description' => 'Non-Current Liabilities', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 3, 'description' => 'Owner\'s Equity', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 4, 'description' => 'Operating Revenue', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 4, 'description' => 'Non-Operating Revenue', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 4, 'description' => 'Other Income', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 5, 'description' => 'Operating Expenses', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 5, 'description' => 'Administrative Expenses', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 5, 'description' => 'Financial Expenses', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 5, 'description' => 'Tax Expenses', 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'account_type_id' => 5, 'description' => 'Non-Operating Expenses', 'created_by' => '1', 'created_at' => now()],

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_categories');
    }
};
