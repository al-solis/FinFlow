<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_code')->nullable()->after('employee_id');
            $table->foreign('vendor_code')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['vendor_code']);

            $table->dropColumn(['vendor_code']);
        });
    }
};