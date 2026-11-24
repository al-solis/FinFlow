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
        Schema::table('rfd_headers', function (Blueprint $table) {
            $table->unsignedTinyInteger('tax_option')->default(0)->comment('0=No Tax, 1=Tax Inclusive, 2=Tax Exclusive')->after('exchange_rate');
        });

        DB::table('rfd_headers')->where('total_tax', '>', 0)->update(['tax_option' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfd_headers', function (Blueprint $table) {
            $table->dropColumn('tax_option');
        });
    }
};
