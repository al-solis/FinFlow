<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safe to run on top of your existing schema: every column is added only when it is missing.
 *
 *  sales_invoices.debit_applied  - total of posted debit memos on the invoice. Kept separate from
 *                                  total_amount so the invoice's own journal and totals never change:
 *                                  amount_due = total_amount + debit_applied - amount_paid - cwt_applied - credit_applied
 *  credit_memos / debit_memos     - the BIR/audit fields the memo forms and posting use.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoices', 'debit_applied')) {
            Schema::table('sales_invoices', function (Blueprint $t) {
                $t->decimal('debit_applied', 15, 2)->default(0);
            });
        }

        foreach (['credit_memos', 'debit_memos'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $add = [
                'reason_code' => fn(Blueprint $t) => $t->string('reason_code', 50)->nullable(),
                'remarks' => fn(Blueprint $t) => $t->text('remarks')->nullable(),
                'bill_to_name' => fn(Blueprint $t) => $t->string('bill_to_name', 150)->nullable(),
                'bill_to_tin' => fn(Blueprint $t) => $t->string('bill_to_tin', 30)->nullable(),
                'bill_to_address' => fn(Blueprint $t) => $t->string('bill_to_address', 500)->nullable(),
                'tax_option' => fn(Blueprint $t) => $t->unsignedTinyInteger('tax_option')->default(0),
                'exchange_rate' => fn(Blueprint $t) => $t->decimal('exchange_rate', 18, 6)->default(1),
                'amount_applied' => fn(Blueprint $t) => $t->decimal('amount_applied', 15, 2)->default(0),
                'amount_unapplied' => fn(Blueprint $t) => $t->decimal('amount_unapplied', 15, 2)->default(0),
                'submitted_by' => fn(Blueprint $t) => $t->unsignedBigInteger('submitted_by')->nullable(),
                'approved_by' => fn(Blueprint $t) => $t->unsignedBigInteger('approved_by')->nullable(),
                'posted_by' => fn(Blueprint $t) => $t->unsignedBigInteger('posted_by')->nullable(),
                'voided_by' => fn(Blueprint $t) => $t->unsignedBigInteger('voided_by')->nullable(),
                'void_reason' => fn(Blueprint $t) => $t->string('void_reason', 255)->nullable(),
            ];

            foreach ($add as $column => $definition) {
                if (!Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $t) use ($definition) {
                        $definition($t);
                    });
                }
            }
        }

        // The memo number is issued on submit, so a draft has none. Make sure the columns allow that
        // (Laravel 10+ can alter without doctrine/dbal). Uncomment if your column is NOT NULL:
        //
        // Schema::table('collections',   fn (Blueprint $t) => $t->string('collection_no', 50)->nullable()->change());
        // Schema::table('credit_memos',  fn (Blueprint $t) => $t->string('credit_memo_no', 50)->nullable()->change());
        // Schema::table('debit_memos',   fn (Blueprint $t) => $t->string('debit_memo_no', 50)->nullable()->change());
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_invoices', 'debit_applied')) {
            Schema::table('sales_invoices', function (Blueprint $t) {
                $t->dropColumn('debit_applied');
            });
        }
        // The memo columns are left in place on purpose: they may hold audit data.
    }
};
