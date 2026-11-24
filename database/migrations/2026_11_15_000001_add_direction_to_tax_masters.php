<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tax_masters', function (Blueprint $table) {
            // Portable: no ->after(). Column position doesn't matter to the app;
            // physical ordering is cosmetic and driver-specific.
            $table->string('direction', 10)->default('both');
            $table->boolean('is_system')->default(false);
        });

        $this->backfillDirection();
    }

    public function down(): void
    {
        Schema::table('tax_masters', function (Blueprint $table) {
            $table->dropColumn('direction');
            $table->dropColumn('is_system');
        });
    }

    /**
     * Best-effort classification of existing taxes as output / input / both,
     * based on which VAT sub-account their gl_account_id points at.
     *
     * Implemented with the query builder (no raw JOIN-UPDATE) so it works
     * identically on MySQL, PostgreSQL, and MSSQL. Chunked to stay memory-safe
     * on large charts of accounts.
     */
    private function backfillDirection(): void
    {
        // Map gl_account_id -> account_subcategories.description so we can
        // classify without SQL dialect-specific JOIN-UPDATE.
        $subAccountDescriptions = DB::table('chart_of_accounts as coa')
            ->join('account_subcategories as sub', 'sub.id', '=', 'coa.account_subcategory_id')
            ->select('coa.id as coa_id', 'sub.description as sub_description')
            ->get();

        $classify = function (string $description): ?string {
            $d = mb_strtolower($description ?? '');

            if (str_contains($d, 'vat payable') || str_contains($d, 'output vat')) {
                return 'output';
            }
            if (str_contains($d, 'vat receivable') || str_contains($d, 'input vat')) {
                return 'input';
            }

            return null; // leave as 'both'
        };

        // Group gl_account_ids by the bucket they belong to. This lets us do
        // two UPDATE ... WHERE IN (...) statements per driver, which every
        // driver supports, instead of one UPDATE ... JOIN.
        $byBucket = ['output' => [], 'input' => []];

        foreach ($subAccountDescriptions as $row) {
            if ($row->sub_description === null) {
                continue;
            }
            $bucket = $classify($row->sub_description);
            if ($bucket !== null) {
                $byBucket[$bucket][] = (int) $row->coa_id;
            }
        }

        foreach ($byBucket as $bucket => $coaIds) {
            if (empty($coaIds)) {
                continue;
            }

            // Chunk IN(...) lists to keep the statement under every driver's
            // parameter limit (MSSQL ~2100, PostgreSQL 65535, MySQL PLENTY).
            foreach (array_chunk($coaIds, 1000) as $chunk) {
                DB::table('tax_masters')
                    ->whereIn('gl_account_id', $chunk)
                    ->update(['direction' => $bucket]);
            }
        }
    }
};