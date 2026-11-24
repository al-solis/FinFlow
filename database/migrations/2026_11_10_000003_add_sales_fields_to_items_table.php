<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * items already serves purchasing (gl_account_id / default_tax_id are the expense side).
     * These columns add the selling side so one item master serves both AP and AR.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('unit_of_measure');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('uom_id')->nullable()->after('description');
            $table->foreign('uom_id')->references('id')->on('unit_of_measures');
            $table->unsignedBigInteger('purchase_uom_id')->nullable()->after('uom_id');
            $table->foreign('purchase_uom_id')->references('id')->on('unit_of_measures');
            $table->boolean('is_sellable')->default(0)->after('default_unit_price');
            $table->unsignedBigInteger('sales_gl_account_id')->nullable()->after('is_sellable');
            $table->foreign('sales_gl_account_id')->references('id')->on('chart_of_accounts');
            $table->unsignedBigInteger('default_sales_tax_id')->nullable()->after('sales_gl_account_id');
            $table->foreign('default_sales_tax_id')->references('id')->on('tax_masters');
            $table->decimal('default_sales_price', 15, 6)->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->foreign('sales_uom_id')->references('id')->on('unit_of_measures');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['uom_id']);
            $table->dropForeign(['purchase_uom_id']);
            $table->dropForeign(['sales_gl_account_id']);
            $table->dropForeign(['default_sales_tax_id']);
            $table->dropForeign(['sales_uom_id']);
            $table->dropColumn(['uom_id', 'purchase_uom_id', 'is_sellable', 'sales_gl_account_id', 'default_sales_tax_id', 'default_sales_price', 'sales_uom_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('unit_of_measure')->nullable();
        });
    }
};
