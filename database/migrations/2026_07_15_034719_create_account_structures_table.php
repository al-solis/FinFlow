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
        Schema::create('account_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 120);
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('status')->comment('0 = Draft, 1 = Generated, 2 = Active, 3 = Closed'); // 0 = Draft, 1 = Generated, 2 = Active, 3 = Closed
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->datetime('last_synced_at')->nullable();
            $table->unsignedBigInteger('last_synced_by')->nullable();
            $table->foreign('last_synced_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_structures');
    }
};
