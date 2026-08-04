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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('days')->default(0);
            $table->integer('status')->default(1); // 1 = Active, 0 = Inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('terms')->insert([
            [
                'code' => '1DAY',
                'name' => '1 Day',
                'description' => 'Payment due in 1 day.',
                'days' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '30DAYS',
                'name' => 'Net 30',
                'description' => 'Payment due in 30 days.',
                'days' => 30,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '60DAYS',
                'name' => 'Net 60',
                'description' => 'Payment due in 60 days.',
                'days' => 60,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '90DAYS',
                'name' => 'Net 90',
                'description' => 'Payment due in 90 days.',
                'days' => 90,
                'status' => 1,
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
        Schema::dropIfExists('terms');
    }
};
