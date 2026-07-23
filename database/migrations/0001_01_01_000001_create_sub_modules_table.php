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
            $table->string('icon')->nullable();
            $table->string('img')->nullable();
            $table->integer('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('sub_modules')->insert([
            'module_id' => 1,
            'code' => 'GL-CHART',
            'name' => 'Chart of Accounts',
            'description' => 'Manage the chart of accounts for the General Ledger module.',
            'icon' => 'fa-book-open',
            'img' => 'chart-of-accounts.png',
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
            'icon' => 'fa-table-columns',
            'img' => 'account-segments.png',
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
            'icon' => 'fa-sitemap',
            'img' => 'account-structure.png',
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
            'icon' => 'fa-calendar-days',
            'img' => 'fiscal-year.png',
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
            'icon' => 'fa-calendar-week',
            'img' => 'accounting-periods.png',
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
            'icon' => 'fa-building',
            'img' => 'vendor-master.png',
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
            'icon' => 'fa-tags',
            'img' => 'vendor-categories.png',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 2,
            'code' => 'AP-TERMS',
            'name' => 'Payment Terms',
            'description' => 'Manage the payment terms for the Accounts Payable module.',
            'icon' => 'fa-calendar-check',
            'img' => 'payment-terms.png',
            'sequence' => 3,
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
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-LIQ',
            'name' => 'Liquidation',
            'description' => 'Manage the liquidations for the Cash Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-REF',
            'name' => 'Refund',
            'description' => 'Manage the refunds for the Cash Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 4,
            'code' => 'CM-REIM',
            'name' => 'Reimbursement',
            'description' => 'Manage the reimbursements for the Cash Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 5,
            'code' => 'BM-BANK',
            'name' => 'Bank Accounts',
            'description' => 'Manage the bank accounts for the Bank Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TM-TY',
            'name' => 'Tax Types',
            'description' => 'Defines the nature of the tax (e.g., VAT, Withholding Tax, etc.) for the Tax Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TM-TRATE',
            'name' => 'Tax Rates',
            'description' => 'Tax percentage rate for the Tax Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TM-TF',
            'name' => 'Tax Formula',
            'description' => 'Specifies how the tax is computed for the Tax Management module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TM-TM',
            'name' => 'Tax Master',
            'description' => 'Main tax definition.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 11,
            'code' => 'TM-TG',
            'name' => 'Tax Group',
            'description' => 'Allows multiple taxes to be attached to a single transaction.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-TB',
            'name' => 'Trial Balance',
            'description' => 'Displays the financial position of an organization at a specific point in time.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-BS',
            'name' => 'Balance Sheet',
            'description' => 'Shows the financial status of an organization at a specific point in time.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-IS',
            'name' => 'Income Statement',
            'description' => 'Shows the financial performance of an organization over a specific period of time.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-CF',
            'name' => 'Cash Flow Statement',
            'description' => 'Shows the inflows and outflows of cash for an organization over a specific period of time.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-GL',
            'name' => 'General Ledger',
            'description' => 'Records all financial transactions of an organization.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 13,
            'code' => 'FR-TAXR',
            'name' => 'Tax Report',
            'description' => 'Generates reports related to tax calculations and filings.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 14,
            'code' => 'MF-COMP',
            'name' => 'Company',
            'description' => 'Displays information about the company.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'SA-USER',
            'name' => 'User',
            'description' => 'Displays information about the user.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'SA-ROLE',
            'name' => 'User Role',
            'description' => 'Displays information about the role.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 15,
            'code' => 'SA-NUM',
            'name' => 'Number Series',
            'description' => 'Displays information about the number series.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sub_modules')->insert([
            'module_id' => 16,
            'code' => 'AW-APPR',
            'name' => 'Approval Workflow',
            'description' => 'Manages approval workflows for various business processes.',
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
