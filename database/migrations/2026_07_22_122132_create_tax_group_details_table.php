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
        Schema::create('tax_group_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_group_id');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups');
            $table->unsignedBigInteger('tax_master_id');
            $table->foreign('tax_master_id')->references('id')->on('tax_masters');
            $table->unsignedInteger('sequence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_group_details');
    }
};
