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
        Schema::create('chart_of_account_segments', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('chart_of_account_id');
            $table->foreign('chart_of_account_id')
                ->references('id')
                ->on('chart_of_accounts');

            // Structure Detail used during generation
            $table->unsignedBigInteger('account_structure_detail_id');
            $table->foreign('account_structure_detail_id')
                ->references('id')
                ->on('account_structure_details');

            // Segment (Company, Department, Project, etc.)
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->foreign('segment_id')
                ->references('id')
                ->on('segments');

            /**
             * ID of the selected value from the corresponding master table.
             *
             * Examples:
             * company_id
             * department_id
             * project_id
             * cost_center_id
             *
             * Since each segment may come from a different master table,
             * we only store its ID.
             */

            $table->unsignedBigInteger('segment_value_id')->nullable();

            /**
             * Snapshot values.
             * These preserve historical data even if master records change.
             */
            $table->string('display_code', 100);

            $table->string('display_name', 255);

            $table->unsignedInteger('sequence');

            $table->timestamps();

            $table->index([
                'chart_of_account_id',
                'sequence'
            ]);

            $table->index([
                'segment_id',
                'segment_value_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_account_segments');
    }
};