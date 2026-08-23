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
        Schema::create('rfd_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->date('request_date');
            $table->date('required_date');
            $table->unsignedBigInteger('term_id')->nullable();
            $table->foreign('term_id')->references('id')->on('terms');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('total_amount', 15, 6)->default(0);
            $table->decimal('total_tax', 15, 6)->default(0);
            $table->decimal('total_discount', 15, 6)->default(0);
            $table->decimal('total_due', 15, 6)->default(0);
            $table->string('remarks')->nullable();
            $table->integer('status')->default(0); // 0 = Draft, 1 = Submitted, 2 = Approved, 3 = Rejected, 4 = Returned
            $table->integer('approval_status')->default(0); // 0 = Draft, 1 = Pending,  2 = Approved, 3 = Rejected, 4 = Returned            
            $table->unsignedTinyInteger('payment_status')->default(0); // 0 = not yet disbursed, 1 = fully disbursed, 2 = partially disbursed (multi-vendor RFDs)
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users');
            $table->timestamp('disbursed_at')->nullable();
            $table->unsignedBigInteger('disbursed_by')->nullable();
            $table->foreign('disbursed_by')->references('id')->on('users');
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
        Schema::dropIfExists('rfd_headers');
    }
};
