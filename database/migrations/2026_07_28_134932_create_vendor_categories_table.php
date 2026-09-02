<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('status')->default(1); // 1 for active, 0 for inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('vendor_categories')->insert([
            [
                'organization_id' => 1,
                'code' => 'SUP',
                'name' => 'General Supplier',
                'description' => 'Office supplies, equipment',
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => 1,
                'code' => 'SER',
                'name' => 'Service Provider',
                'description' => 'Contractors, consultants',
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => 1,
                'code' => 'UTL',
                'name' => 'Utility Company',
                'description' => 'Electric, water, internet',
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => 1,
                'code' => 'GOV',
                'name' => 'Government Agency',
                'description' => 'Public sector organizations',
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => 1,
                'code' => 'EMP',
                'name' => 'Employee',
                'description' => 'Employee vendors for cash advances and reimbursements',
                'status' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_categories');
    }
};
