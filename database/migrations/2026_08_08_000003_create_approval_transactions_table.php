<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_workflow_id');

            // Polymorphic link to the actual document: rfd_header, payment_header, etc.
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');

            $table->unsignedInteger('current_step_no')->default(1);
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'returned'])
                ->default('pending');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows');
            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_transactions');
    }
};
