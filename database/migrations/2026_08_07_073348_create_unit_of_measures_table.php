<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Dated one second BEFORE create_items_table (2026_08_07_073349) because items reference it.
 *
 * Conversion model:  1 <this UOM> = factor x <base UOM>
 *   base_uom_id NULL  => this is a base unit (factor is always 1)   e.g. PC, KG, HR
 *   base_uom_id set   => derived unit; the base must itself be a base unit (no chains)
 *   BOX -> base PC, factor 12   means   1 BOX = 12 PC
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->unsignedBigInteger('base_uom_id')->nullable();
            $table->foreign('base_uom_id')->references('id')->on('unit_of_measures');
            $table->decimal('factor', 18, 6)->default(1);
            $table->integer('status')->default(1); // 1 = Active, 0 = Inactive
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        $now = now();
        $row = fn($code, $name, $baseId = null, $factor = 1) => [
            'organization_id' => 1,
            'code' => $code,
            'name' => $name,
            'base_uom_id' => $baseId,
            'factor' => $factor,
            'status' => 1,
            'created_by' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Sample base units
        foreach ([
            ['PC', 'Piece'],
            ['KG', 'Kilogram'],
            ['L', 'Liter'],
            ['HR', 'Hour'],
            ['DAY', 'Day'],
            ['MONTH', 'Month'],
            ['SET', 'Set'],
            ['LOT', 'Lot']
        ] as [$code, $name]) {
            DB::table('unit_of_measures')->insert($row($code, $name));
        }

        // Sample derived units 
        $id = fn($code) => DB::table('unit_of_measures')->where('organization_id', 1)->where('code', $code)->value('id');
        foreach ([['BOX', 'Box', 'PC', 12], ['DOZ', 'Dozen', 'PC', 12], ['G', 'Gram', 'KG', 0.001], ['ML', 'Milliliter', 'L', 0.001]] as [$code, $name, $base, $factor]) {
            DB::table('unit_of_measures')->insert($row($code, $name, $id($base), $factor));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
