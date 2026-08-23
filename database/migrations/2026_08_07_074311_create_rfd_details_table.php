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
        Schema::create('rfd_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfd_header_id');
            $table->foreign('rfd_header_id')->references('id')->on('rfd_headers');
            $table->integer('line_no');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')->references('id')->on('items');
            $table->text('description');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->unsignedBigInteger('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('chart_of_accounts');
            $table->decimal('quantity', 15, 6)->default(0);
            $table->decimal('unit_price', 15, 6)->default(0);
            $table->decimal('discount_amount', 15, 6)->default(0);
            $table->decimal('taxable_amount', 15, 6)->default(0);
            $table->decimal('tax_amount', 15, 6)->default(0);
            $table->decimal('total_amount', 15, 6)->default(0);
            $table->string('reference', 50)->nullable();
            $table->date('reference_date')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('rfd_details');
    }
};
