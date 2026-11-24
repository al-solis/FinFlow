<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ASSUMPTION WARNING: same as the AR seed - I have not seen create_sub_modules_table.php.
 * Match group/icon/img/src to your existing rows. Route name for code 'MASTER-ITEM' is 'master.item'.
 */
return new class extends Migration {
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('code', 'MASTER')->value('id');
        if (!$moduleId || DB::table('sub_modules')->where('code', 'MASTER-ITEM')->exists()) {
            return;
        }

        $id = DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'MASTER-ITEM',
            'name' => 'Items',
            'description' => 'Items and services used in purchasing and sales',
            'group' => null,
            'icon' => 'fa fa-boxes',
            'img' => 'item.png',
            'src' => '',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('access_rights')->insert([
            'organization_id' => 1,
            'role_id' => 1,
            'module_id' => $moduleId,
            'sub_module_id' => $id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'MASTER-UOM',
            'name' => 'Units of Measure',
            'description' => 'Units of measure for items and services',
            'group' => null,
            'icon' => 'fa fa-ruler',
            'img' => 'uom.png',
            'src' => '',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('access_rights')->insert([
            'organization_id' => 1,
            'role_id' => 1,
            'module_id' => $moduleId,
            'sub_module_id' => $id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


    }

    public function down(): void
    {
        $id = DB::table('sub_modules')->where('code', 'MASTER-ITEM')->value('id');
        if ($id) {
            DB::table('access_rights')->where('sub_module_id', $id)->delete();
            DB::table('sub_modules')->where('id', $id)->delete();
        }

        $id = DB::table('sub_modules')->where('code', 'MASTER-UOM')->value('id');
        if ($id) {
            DB::table('access_rights')->where('sub_module_id', $id)->delete();
            DB::table('sub_modules')->where('id', $id)->delete();
        }
    }
};