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
        Schema::create('chart_of_accounts', function (Blueprint $table) {

            $table->id();

            // Structure used to generate this COA
            $table->unsignedBigInteger('account_structure_id');
            $table->foreign('account_structure_id')
                ->references('id')
                ->on('account_structures');

            // Materialized account code
            $table->string('account_code', 255)->unique();

            $table->string('account_name', 255);

            // Source account
            $table->unsignedBigInteger('main_account_id');
            $table->foreign('main_account_id')
                ->references('id')
                ->on('main_accounts');

            $table->unsignedBigInteger('account_type_id');
            $table->foreign('account_type_id')
                ->references('id')
                ->on('account_types');

            $table->unsignedBigInteger('account_category_id');
            $table->foreign('account_category_id')
                ->references('id')
                ->on('account_categories');

            $table->unsignedBigInteger('account_subcategory_id');
            $table->foreign('account_subcategory_id')
                ->references('id')
                ->on('account_subcategories');


            // Posting Account?
            $table->boolean('is_posting')->default(true);

            $table->boolean('status')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users');

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')
                ->references('id')
                ->on('users');

            $table->timestamps();

            $table->index('account_code');
            $table->index('main_account_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};