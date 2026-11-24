<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\module;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sub_modules', function (Blueprint $table) {
            //
        });

        $moduleId = DB::table('modules')->where('code', 'AR')->value('id');

        DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'AR-CMEMO',
            'name' => 'Credit Memo',
            'description' => 'Issue credit memos to reduce customer receivables (returns, overbills, discounts)',
            'group' => '',
            'icon' => 'fa-file-circle-minus',
            'img' => 'arcm.png',
            'src' => '',
            'sequence' => 8,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => $moduleId,
            'code' => 'AR-DMEMO',
            'name' => 'Debit Memo',
            'description' => 'Issue debit memos to increase customer receivables (underbills, add-ons)',
            'group' => '',
            'icon' => 'fa-file-circle-plus',
            'img' => 'ardm.png',
            'src' => '',
            'sequence' => 8,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_modules', function (Blueprint $table) {
            //
        });


        $id = DB::table('sub_modules')->where('code', 'AR-CMEMO')->value('id');
        if ($id) {
            DB::table('access_rights')->where('sub_module_id', $id)->delete();
            DB::table('sub_modules')->where('id', $id)->delete();
        }

        $id = DB::table('sub_modules')->where('code', 'AR-DMEMO')->value('id');
        if ($id) {
            DB::table('access_rights')->where('sub_module_id', $id)->delete();
            DB::table('sub_modules')->where('id', $id)->delete();
        }
    }
};
