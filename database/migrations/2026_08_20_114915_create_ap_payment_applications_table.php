<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ap_payment_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ap_payment_id');
            $table->foreign('ap_payment_id')->references('id')->on('ap_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('ap_invoice_id');
            $table->foreign('ap_invoice_id')->references('id')->on('ap_invoices');
            $table->decimal('amount_applied', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_payment_applications');
    }
};