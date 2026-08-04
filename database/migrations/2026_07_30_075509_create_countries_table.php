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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('iso2', 2)->nullable();
            $table->string('iso3', 3)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('dial_code', 10)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        DB::table('countries')->insert([
            ['code' => 'PH', 'name' => 'Philippines', 'iso2' => 'PH', 'iso3' => 'PHL', 'currency_code' => 'PHP', 'dial_code' => '+63', 'status' => 1, 'created_by' => 1, 'updated_by' => 1],
            ['code' => 'US', 'name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'currency_code' => 'USD', 'dial_code' => '+1', 'status' => 1, 'created_by' => 1, 'updated_by' => 1],
            ['code' => 'CA', 'name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'currency_code' => 'CAD', 'dial_code' => '+1', 'status' => 1, 'created_by' => 1, 'updated_by' => 1],
            ['code' => 'GB', 'name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'currency_code' => 'GBP', 'dial_code' => '+44', 'status' => 1, 'created_by' => 1, 'updated_by' => 1],
            ['code' => 'AU', 'name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS', 'currency_code' => 'AUD', 'dial_code' => '+61', 'status' => 1, 'created_by' => 1, 'updated_by' => 1],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
