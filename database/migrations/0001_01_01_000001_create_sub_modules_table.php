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
        Schema::create('sub_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->foreign('module_id')->references('id')->on('modules');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('group', 50)->nullable();
            $table->string('icon')->nullable();
            $table->string('img')->nullable();
            $table->string('src')->nullable();
            $table->integer('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-CHART',
            'name' => 'Chart of Accounts',
            'description' => 'Manage the chart of accounts for the General Ledger module.',
            'group' => 'Maintenance',
            'icon' => 'fa-book-open',
            'img' => 'chart-of-accounts.png',
            'src' => 'https://img.icons8.com/stickers/100/logbook.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-SEGMENTS',
            'name' => 'Account Segments',
            'description' => 'Manage the account segments for the General Ledger module.',
            'group' => 'Maintenance',
            'icon' => 'fa-table-columns',
            'img' => 'account-segments.png',
            'src' => 'https://img.icons8.com/stickers/100/columns.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-STRUCTURE',
            'name' => 'Account Structure',
            'description' => 'Manage the account structure for the General Ledger module.',
            'group' => 'Maintenance',
            'icon' => 'fa-sitemap',
            'img' => 'account-structure.png',
            'src' => 'https://img.icons8.com/offices/30/parallel-tasks.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-FISCAL',
            'name' => 'Fiscal Year Management',
            'description' => 'Manage the fiscal year settings',
            'group' => 'Maintenance',
            'icon' => 'fa-calendar-days',
            'img' => 'fiscal-year.png',
            'src' => 'https://img.icons8.com/stickers/100/tear-off-calendar.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-PERIODS',
            'name' => 'Accounting Periods',
            'description' => 'Manage the accounting periods for the General Ledger module.',
            'group' => 'Maintenance',
            'icon' => 'fa-calendar-week',
            'img' => 'accounting-periods.png',
            'src' => 'https://img.icons8.com/stickers/100/overtime.png',
            'sequence' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-JOURNAL',
            'name' => 'Journal Entry',
            'description' => 'Manage journal entries for the General Ledger module.',
            'icon' => 'fa-book',
            'img' => 'journal-entry.png',
            'src' => 'https://img.icons8.com/color/48/goodnotes.png',
            'sequence' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-TRIAL',
            'name' => 'Trial Balance',
            'description' => 'Manage the trial balance for the General Ledger module.',
            'icon' => 'fa-scale-balanced',
            'img' => 'trial-balance.png',
            'src' => 'https://img.icons8.com/fluency/48/scales.png',
            'sequence' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-GR',
            'name' => 'General Ledger',
            'description' => 'Manage the general ledger for the General Ledger module.',
            'icon' => 'fa-book-bookmark',
            'img' => 'general-ledger.png',
            'src' => 'https://img.icons8.com/color-glass/48/document-1.png',
            'sequence' => 8,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-VENDORS',
            'name' => 'Vendor Master',
            'description' => 'Manage the vendor master data for the Accounts Payable module.',
            'group' => 'Maintenance',
            'icon' => 'fa-building',
            'img' => 'vendor-master.png',
            'src' => 'https://img.icons8.com/color/48/company.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-CATEGORIES',
            'name' => 'Vendor Categories',
            'description' => 'Manage the vendor categories for the Accounts Payable module.',
            'group' => 'Maintenance',
            'icon' => 'fa-tags',
            'img' => 'vendor-categories.png',
            'src' => 'https://img.icons8.com/ultraviolet/40/categorize.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-RFD',
            'name' => 'Request for Disbursement',
            'description' => 'Manage the request for disbursement for the Accounts Payable module.',
            'icon' => 'fa-file-invoice-dollar',
            'img' => 'request-for-disbursement.png',
            'src' => 'https://img.icons8.com/fluency/48/document.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-PI',
            'name' => 'Purchase Invoice',
            'description' => 'Manage the purchase invoices for the Accounts Payable module.',
            'icon' => 'fa-file-invoice',
            'img' => 'purchase-invoice.png',
            'src' => 'https://img.icons8.com/fluency/48/invoice.png',
            'sequence' => 5,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-DM',
            'name' => 'Debit Memo',
            'description' => 'Manage the debit memos for the Accounts Payable module.',
            'icon' => 'fa-file-circle-plus',
            'img' => 'debit-memo.png',
            'src' => 'https://img.icons8.com/flat-round/64/plus.png',
            'sequence' => 6,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-CM',
            'name' => 'Credit Memo',
            'description' => 'Manage the credit memos for the Accounts Payable module.',
            'icon' => 'fas fa-list',
            'img' => 'credit-memo.png',
            'src' => 'https://img.icons8.com/fluency/48/minus.png',
            'sequence' => 7,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-PV',
            'name' => 'Payment Voucher',
            'description' => 'Manage the payment vouchers for the Accounts Payable module.',
            'icon' => 'fas fa-list',
            'img' => 'payment-voucher.png',
            'src' => 'https://img.icons8.com/cotton/64/card-in-use--v1.png',
            'sequence' => 8,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-CR',
            'name' => 'Cash Receipt',
            'description' => 'Manage the cash receipts for the Cash Management module.',
            'icon' => 'fas fa-list',
            'img' => 'cash-receipt.png',
            'src' => 'https://img.icons8.com/cotton/64/cash-receipt.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-DV',
            'name' => 'Disbursement',
            'description' => 'Manage the cash disbursements for the Cash Management module.',
            'icon' => 'fas fa-list',
            'img' => 'disbursement.png',
            'src' => 'https://img.icons8.com/external-filled-outline-wichaiwi/64/external-disbursement-business-process-outsourcing-filled-outline-wichaiwi.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-PC',
            'name' => 'Petty Cash',
            'description' => 'Manage the petty cash for the Cash Management module.',
            'icon' => 'fa wallet',
            'img' => 'petty-cash.png',
            'src' => 'https://img.icons8.com/color/48/wallet--v1.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-CA',
            'name' => 'Cash Advance',
            'description' => 'Manage the cash advances for the Cash Management module.',
            'icon' => 'fa money-bill',
            'img' => 'cash-advance.png',
            'src' => 'https://img.icons8.com/external-flaticons-flat-flat-icons/64/external-cash-advance-finance-flaticons-flat-flat-icons.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-LIQ',
            'name' => 'Liquidation',
            'description' => 'Manage the liquidations for the Cash Management module.',
            'icon' => 'fa money-bill',
            'img' => 'liquidation.png',
            'src' => 'https://img.icons8.com/3d-fluency/94/inspection.png',
            'sequence' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-REF',
            'name' => 'Refund',
            'description' => 'Manage the refunds for the Cash Management module.',
            'icon' => 'fa money-bill',
            'img' => 'refund.png',
            'src' => 'https://img.icons8.com/color/48/refund.png',
            'sequence' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-REIM',
            'name' => 'Reimbursement',
            'description' => 'Manage the reimbursements for the Cash Management module.',
            'icon' => 'fa money-bill',
            'img' => 'reimbursement.png',
            'src' => 'https://img.icons8.com/fluency/48/cash--v1.png',
            'sequence' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 5,
            'code' => 'BM-RECON',
            'name' => 'Bank Reconciliation',
            'description' => 'Bank reconciliation for the Bank Management module.',
            'icon' => 'fa bank',
            'img' => 'bank-recon.png',
            'src' => 'https://img.icons8.com/fluency/48/transfer-money.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 5,
            'code' => 'BM-BANK',
            'name' => 'Bank Accounts',
            'description' => 'Manage the bank accounts for the Bank Management module.',
            'group' => 'Maintenance',
            'icon' => 'fa bank',
            'img' => 'bank-accounts.png',
            'src' => 'https://img.icons8.com/stickers/100/merchant-account.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TAX-TY',
            'name' => 'Tax Types',
            'description' => 'Defines the nature of the tax (e.g., VAT, Withholding Tax, etc.) for the Tax Management module.',
            'icon' => 'fa percent',
            'img' => 'tax-types.png',
            'src' => 'https://img.icons8.com/material-rounded/24/percentage.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TAX-TRATE',
            'name' => 'Tax Rates',
            'description' => 'Tax percentage rate for the Tax Management module.',
            'icon' => 'fa percent',
            'img' => 'tax-rates.png',
            'src' => 'https://img.icons8.com/ios/50/income-tax.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TAX-TF',
            'name' => 'Tax Formula',
            'description' => 'Specifies how the tax is computed for the Tax Management module.',
            'icon' => 'fa calculator',
            'img' => 'tax-formula.png',
            'src' => 'https://img.icons8.com/color/48/calculator--v1.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TAX-TM',
            'name' => 'Tax Master',
            'description' => 'Main tax definition.',
            'icon' => 'fa book',
            'img' => 'tax-master.png',
            'src' => 'https://img.icons8.com/emoji/48/bar-chart-emoji.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TAX-TG',
            'name' => 'Tax Group',
            'description' => 'Allows multiple taxes to be attached to a single transaction.',
            'icon' => 'fa users',
            'img' => 'tax-group.png',
            'src' => 'https://img.icons8.com/external-tal-revivo-color-tal-revivo/48/external-word-processing-bullet-list-pattern-isolated-on-a-white-background-web-color-tal-revivo.png',
            'sequence' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'group' => 'Maintenance',
            'code' => 'FIN-TB',
            'name' => 'Trial Balance',
            'description' => 'Displays the financial position of an organization at a specific point in time.',
            'icon' => 'fa balance-scale',
            'img' => 'trial-balance.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FIN-BS',
            'name' => 'Balance Sheet',
            'description' => 'Shows the financial status of an organization at a specific point in time.',
            'icon' => 'fa chart-pie',
            'img' => 'balance-sheet.png',
            'src' => 'https://img.icons8.com/parakeet-line/48/document.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FIN-IS',
            'name' => 'Income Statement',
            'description' => 'Shows the financial performance of an organization over a specific period of time.',
            'icon' => 'fa chart-line',
            'img' => 'income-statement.png',
            'src' => 'https://img.icons8.com/plasticine/100/graph.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FIN-CF',
            'name' => 'Cash Flow Statement',
            'description' => 'Shows the inflows and outflows of cash for an organization over a specific period of time.',
            'icon' => 'fa money-bill',
            'img' => 'cash-flow-statement.png',
            'src' => 'https://img.icons8.com/external-nawicon-flat-nawicon/64/external-Cash-Flow-money-management-nawicon-flat-nawicon.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FIN-GL',
            'name' => 'General Ledger',
            'description' => 'Records all financial transactions of an organization.',
            'icon' => 'fa book',
            'img' => 'general-ledger.png',
            'sequence' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FIN-TAXR',
            'name' => 'Tax Report',
            'description' => 'Generates reports related to tax calculations and filings.',
            'icon' => 'fa file-alt',
            'img' => 'tax-report.png',
            'src' => 'https://img.icons8.com/external-filled-outline-berkahicon/64/external-calculator-fintech-filled-outline-berkahicon.png',
            'sequence' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-USER',
            'name' => 'User',
            'description' => 'Displays information about the user.',
            'icon' => 'fa-users',
            'img' => 'user.png',
            'src' => 'https://img.icons8.com/external-anggara-flat-anggara-putra/32/external-group-basic-user-interface-anggara-flat-anggara-putra.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-ROLE',
            'name' => 'User Role',
            'description' => 'Displays information about the role.',
            'icon' => 'fa-user-tag',
            'img' => 'user-role.png',
            'src' => 'https://img.icons8.com/dusk/64/admin-settings-male.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-ACCESS',
            'name' => 'Access Rights',
            'description' => 'Manages access rights for various system features.',
            'icon' => 'fa-lock',
            'img' => 'access-rights.png',
            'src' => 'https://img.icons8.com/dusk/64/user-shield.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-ORG',
            'name' => 'Organization',
            'description' => 'Displays information and settings about your orgranization.',
            'group' => 'Maintenance',
            'icon' => 'fa-building',
            'img' => 'company.png',
            'src' => 'https://img.icons8.com/color/48/company.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-CURRENCY',
            'name' => 'Currency',
            'description' => 'Manage the currency settings.',
            'group' => 'Maintenance',
            'icon' => 'fa-dollar-sign',
            'img' => 'currency.png',
            'src' => 'https://img.icons8.com/color/48/currency-exchange.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-TERMS',
            'name' => 'Payment Terms',
            'description' => 'Manage the payment terms.',
            'group' => 'Maintenance',
            'icon' => 'fa-calendar-check',
            'img' => 'terms.png',
            'src' => 'https://img.icons8.com/emoji/48/calendar-emoji.png',
            'sequence' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-METHOD',
            'name' => 'Payment Methods',
            'description' => 'Manage the payment methods.',
            'group' => 'Maintenance',
            'icon' => 'fa-credit-card',
            'img' => 'payment-methods.png',
            'src' => 'https://img.icons8.com/3d-fluency/94/payment-method.png',
            'sequence' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'ADMIN-NUM',
            'name' => 'Number Series',
            'description' => 'Displays information about the number series.',
            'group' => 'Maintenance',
            'icon' => 'fa-sort-numeric-up',
            'img' => 'number-series.png',
            'src' => 'https://img.icons8.com/external-filled-outline-berkahicon/64/external-calculator-fintech-filled-outline-berkahicon.png',
            'sequence' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 16,
            'code' => 'APPW-APPR',
            'name' => 'Approval Workflow',
            'description' => 'Manages approval workflows for various business processes.',
            'icon' => 'fa-tasks',
            'img' => 'approval-workflow.png',
            'src' => 'https://img.icons8.com/skeuomorphism/64/workflow.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_modules');
    }
};
