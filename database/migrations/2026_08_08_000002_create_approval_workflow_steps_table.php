<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_workflow_id');
            $table->unsignedInteger('step_no');
            $table->string('step_name'); // e.g. "Accounting Review", "Finance Manager Approval"

            // Who acts on this step. Support either a role or a specific user.
            $table->enum('approver_type', ['role', 'user'])->default('role');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // What this step is allowed to do to the transaction while it sits with them
            $table->boolean('can_edit_chart_of_account')->default(false);
            $table->boolean('can_edit_tax')->default(false);
            $table->boolean('can_edit_amount')->default(false);
            $table->boolean('can_return_to_requester')->default(true);

            // Marks the step that, once approved, releases the document downstream
            // (e.g. to payment processing)
            $table->boolean('is_final_approval')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows')->cascadeOnDelete();
            $table->unique(['approval_workflow_id', 'step_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};
