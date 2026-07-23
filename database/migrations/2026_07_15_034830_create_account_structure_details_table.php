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
        Schema::create('account_structure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_structure_id')->constrained('account_structures');

            $table->enum('source_type', ['segment', 'main_account']);
            // Only 'segment' rows reference a real row. 'main_account' rows are a
            // fixed marker (there's no single main_account to point to here).
            $table->foreignId('segment_id')->nullable()->constrained('segments');

            $table->unsignedInteger('sequence');
            $table->string('separator', 2)->nullable();
            $table->timestamps();

            $table->unique(['account_structure_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_structure_details');
    }
};
