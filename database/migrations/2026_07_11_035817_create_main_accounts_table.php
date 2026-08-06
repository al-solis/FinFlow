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
        Schema::create('main_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('code', 20);
            $table->string('description', 150);
            $table->unsignedBigInteger('account_type_id');
            $table->foreign('account_type_id')->references('id')->on('account_types');
            $table->unsignedBigInteger('account_category_id');
            $table->foreign('account_category_id')->references('id')->on('account_categories');
            $table->unsignedBigInteger('account_subcategory_id');
            $table->foreign('account_subcategory_id')->references('id')->on('account_subcategories');
            $table->integer('status')->default(1); // 1 for active, 0 for inactive
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('main_accounts')->insert([
            ['organization_id' => 1, 'code' => '1000', 'description' => 'Cash on Hand', 'account_type_id' => 1, 'account_category_id' => 1, 'account_subcategory_id' => 1, 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'code' => '1100', 'description' => 'Accounts Receivable', 'account_type_id' => 1, 'account_category_id' => 1, 'account_subcategory_id' => 2, 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'code' => '2001', 'description' => 'Accounts Payable', 'account_type_id' => 2, 'account_category_id' => 3, 'account_subcategory_id' => 3, 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'code' => '3001', 'description' => 'Owner\'s Equity', 'account_type_id' => 3, 'account_category_id' => 5, 'account_subcategory_id' => 4, 'created_by' => 1, 'created_at' => now()],
            ['organization_id' => 1, 'code' => '4001', 'description' => 'Sales Revenue', 'account_type_id' => 4, 'account_category_id' => 6, 'account_subcategory_id' => 5, 'created_by' => 1, 'created_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_accounts');
    }
};
