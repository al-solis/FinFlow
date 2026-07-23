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
        Schema::create('account_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_category_id');
            $table->foreign('account_category_id')->references('id')->on('account_categories');
            $table->string('description', 150);
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('account_subcategories')->insert([
            ['account_category_id' => 1, 'description' => 'Cash and Cash Equivalents', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 1, 'description' => 'Accounts Receivable', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 1, 'description' => 'Inventory', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 1, 'description' => 'Prepaid Expenses', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 1, 'description' => 'Short-term Investments', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Property, Plant, and Equipment', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Vehicles', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Furniture & Fixtures', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Computer Equipment', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Intangible Assets', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 2, 'description' => 'Accumulated Depreciation', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 3, 'description' => 'Common Stock', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 3, 'description' => 'Retained Earnings', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 4, 'description' => 'Sales Revenue', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 4, 'description' => 'Service Revenue', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 5, 'description' => 'Cost of Goods Sold', 'created_by' => '1', 'created_at' => now()],
            ['account_category_id' => 5, 'description' => 'Operating Expenses', 'created_by' => '1', 'created_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_subcategories');
    }
};
