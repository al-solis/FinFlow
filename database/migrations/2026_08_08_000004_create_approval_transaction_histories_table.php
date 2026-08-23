<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_transaction_id');
            $table->unsignedBigInteger('approval_workflow_step_id')->nullable();
            $table->unsignedInteger('step_no');

            $table->enum('action', ['submitted', 'approved', 'rejected', 'returned', 'edited'])
                ->default('submitted');

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('approval_transaction_id')->references('id')->on('approval_transactions');
            $table->foreign('approval_workflow_step_id')->references('id')->on('approval_workflow_steps');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_transaction_histories');
    }
};
