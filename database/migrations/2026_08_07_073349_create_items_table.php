<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()
                ->constrained('organizations');

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->nullable(); // e.g. pcs, box, hr, kg

            // Default GL account this item posts to on a disbursement/expense line —
            // lines can still override it per the approver's edit permissions.
            $table->foreignId('gl_account_id')->nullable()
                ->constrained('chart_of_accounts')->nullOnDelete();

            // Default tax auto-applied when this item is picked on a line, optional.
            $table->foreignId('default_tax_id')->nullable()
                ->constrained('tax_masters')->nullOnDelete();

            $table->decimal('default_unit_price', 15, 6)->nullable();

            $table->integer('status')->default(1); // 1 for active, 0 for inactive

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};