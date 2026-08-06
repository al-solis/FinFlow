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
        Schema::create('access_rights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('module_id');
            $table->foreign('module_id')->references('id')->on('modules');
            $table->unsignedBigInteger('sub_module_id')->nullable();
            $table->foreign('sub_module_id')->references('id')->on('sub_modules');
            $table->boolean('can_create')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });

        DB::table('access_rights')->insertUsing(
            [
                'organization_id',
                'role_id',
                'module_id',
                'sub_module_id',
                'can_create',
                'can_read',
                'can_update',
                'can_delete',
                'created_by',
                'created_at',
            ],
            DB::table('sub_modules')->selectRaw(
                '1,
                1,
                module_id,
                id,
                1,
                1,
                1,
                1,
                1,
                ?',
                [now()]
            )
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_rights');
    }
};
