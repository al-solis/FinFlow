<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('locale', 20)
                ->default('en-PH')
                ->after('language');
        });

        // Set the locale for existing organizations
        DB::table('organizations')
            ->whereNull('locale')
            ->update([
                'locale' => 'en-PH',
            ]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};