<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('status')->default(1); // 1 = Active, 0 = Inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        DB::table('customer_categories')->insert([
            ['organization_id' => 1, 'code' => 'PRV', 'name' => 'Private Corporation', 'description' => 'Private companies and businesses', 'status' => 1, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => 1, 'code' => 'GOV', 'name' => 'Government Agency', 'description' => 'Public sector (usually a withholding agent)', 'status' => 1, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => 1, 'code' => 'IND', 'name' => 'Individual', 'description' => 'Individual / walk-in customers', 'status' => 1, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_categories');
    }
};
