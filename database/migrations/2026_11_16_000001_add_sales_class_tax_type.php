<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\tax_type;
use App\Models\tax_master;
use App\Models\tax_formula;

return new class extends Migration {
    public function up(): void
    {
        // Insert for every organization that already exists. New orgs are
        // handled by the seeder below.
        $orgs = DB::table('organizations')->pluck('id');
        $now = now();

        foreach ($orgs as $orgId) {
            $exists = DB::table('tax_types')
                ->where('organization_id', $orgId)
                ->where('code', 'SALES_CLASS')
                ->exists();

            if ($exists) {
                continue;
            }

            $classType = tax_type::firstOrCreate(
                ['organization_id' => $orgId, 'code' => 'SALES_CLASS'],
                [
                    'name' => 'Sales Classification',
                    'description' => 'Non-VAT sales treatments: Zero-Rated, VAT-Exempt, Non-VAT.',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $zeroFormula = tax_formula::where('organization_id', $orgId)
                ->where('type', 'Percentage')
                ->where('operation', 'Add')
                ->where(fn($q) => $q->where('expression', '0')->orWhere('expression', '0.00'))
                ->first();

            if (!$zeroFormula) {
                $zeroFormula = tax_formula::create([
                    'organization_id' => $orgId,
                    'code' => 'ZERO',
                    'name' => 'Zero Rate',
                    'type' => 'Percentage',
                    'operation' => 'Add',
                    'expression' => '0',
                    'rounding' => 'Round',
                    'decimal_places' => 2,
                    'status' => 1,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 3. The three taxes.
            $taxes = [
                [
                    'code' => 'ZERO-RATED',
                    'name' => 'Zero-Rated Sale',
                    'direction' => 'both',   // shows on invoices and bills
                ],
                [
                    'code' => 'VAT-EXEMPT',
                    'name' => 'VAT-Exempt Sale',
                    'direction' => 'both',
                ],
                [
                    'code' => 'NON-VAT',
                    'name' => 'Non-VAT Sale',
                    'direction' => 'both',
                ],
            ];

            foreach ($taxes as $tax) {
                tax_master::updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'code' => $tax['code'],
                    ],
                    [
                        'name' => $tax['name'],
                        'tax_type_id' => $classType->id,
                        'tax_formula_id' => $zeroFormula->id,
                        'rate' => 0,
                        'fixed_amount' => 0,
                        'gl_account_id' => null,
                        'recoverable' => false,
                        'direction' => $tax['direction'],
                        'is_system' => 1,
                        'priority' => 999,
                        'effective_from' => '2000-01-01',
                        'effective_to' => null,
                        'status' => 1,
                        'created_by' => null,
                        'updated_by' => null,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('tax_types')->where('code', 'SALES_CLASS')->delete();
        DB::table('tax_formulas')->where('code', 'ZERO')->delete();
        DB::table('tax_master')->where('tax_type_id', function ($query) {
            $query->select('id')
                ->from('tax_types')
                ->where('code', 'SALES_CLASS');
        })->delete();
    }
};