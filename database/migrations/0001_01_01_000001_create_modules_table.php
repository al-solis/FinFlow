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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('img')->nullable();
            $table->string('src')->nullable();
            $table->integer('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('modules')->insert([
            'code' => 'GL',
            'name' => 'General Ledger',
            'description' => 'General Ledger',
            'icon' => 'fas fa-book',
            'img' => 'gl.png',
            'src' => 'https://img.icons8.com/arcade/64/ledger.png',
            'sequence' => 12,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'AP',
            'name' => 'Accounts Payable',
            'description' => 'Accounts Payable',
            'icon' => 'fas fa-money-bill-alt',
            'img' => 'ap.png',
            'src' => 'https://img.icons8.com/plasticine/100/invoice.png',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'AR',
            'name' => 'Accounts Receivable',
            'description' => 'Accounts Receivable',
            'icon' => 'fas fa-money-bill-wave',
            'img' => 'ar.png',
            'src' => 'https://img.icons8.com/fluency/48/receipt.png',
            'sequence' => 2,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'CM',
            'name' => 'Cash Management',
            'description' => 'Cash Management',
            'icon' => 'fas fa-money-check',
            'img' => 'cm.png',
            'src' => 'https://img.icons8.com/plasticine/100/cash--v2.png',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'BM',
            'name' => 'Bank Management',
            'description' => 'Bank Management',
            'icon' => 'fas fa-university',
            'img' => 'bm.png',
            'src' => 'https://img.icons8.com/arcade/64/merchant-account.png',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'FA',
            'name' => 'Fixed Assets',
            'description' => 'Fixed Assets',
            'icon' => 'fas fa-building',
            'img' => 'fa.png',
            'src' => 'https://img.icons8.com/arcade/64/fixed-assets.png',
            'sequence' => 5,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'IA',
            'name' => 'Inventory Accounting',
            'description' => 'Inventory Accounting',
            'icon' => 'fas fa-boxes',
            'img' => 'ia.png',
            'src' => 'https://img.icons8.com/arcade/64/garage-closed.png',
            'sequence' => 6,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'PUR',
            'name' => 'Purchasing',
            'description' => 'Purchasing',
            'icon' => 'fas fa-shopping-cart',
            'img' => 'pur.png',
            'src' => 'https://img.icons8.com/arcade/64/shopping-cart-loaded.png',
            'sequence' => 7,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'SALES',
            'name' => 'Sales',
            'description' => 'Sales',
            'icon' => 'fas fa-tag',
            'img' => 'sales.png',
            'src' => 'https://img.icons8.com/fluency/48/bullish.png',
            'sequence' => 8,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'BUDGET',
            'name' => 'Budget Management',
            'description' => 'Budget Management',
            'icon' => 'fas fa-chart-line',
            'img' => 'budget.png',
            'src' => 'https://img.icons8.com/arcade/64/bar-chart.png',
            'sequence' => 9,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'TAX',
            'name' => 'Tax Management',
            'description' => 'Tax Management',
            'icon' => 'fas fa-percent',
            'img' => 'tax.png',
            'src' => 'https://img.icons8.com/arcade/64/dividends.png',
            'sequence' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'COST',
            'name' => 'Cost Accounting',
            'description' => 'Cost Accounting',
            'icon' => 'fas fa-dollar-sign',
            'img' => 'cost.png',
            'src' => 'https://img.icons8.com/arcade/64/calculator.png',
            'sequence' => 11,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'FIN',
            'name' => 'Financial Reporting',
            'description' => 'Financial Reporting',
            'icon' => 'fas fa-chart-bar',
            'img' => 'fin.png',
            'src' => 'https://img.icons8.com/nolan/64/bullish.png',
            'sequence' => 13,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'MASTER',
            'name' => 'Master Files',
            'description' => 'Master Files',
            'icon' => 'fas fa-file-alt',
            'img' => 'master.png',
            'src' => 'https://img.icons8.com/pulsar-gradient/48/database.png',
            'sequence' => 14,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'ADMIN',
            'name' => 'System Administration',
            'description' => 'System Administration',
            'icon' => 'fas fa-cogs',
            'img' => 'admin.png',
            'src' => 'https://img.icons8.com/arcade/64/settings.png',
            'sequence' => 16,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modules')->insert([
            'code' => 'APPW',
            'name' => 'Approval Workflow',
            'description' => 'Approval Workflow',
            'icon' => 'fas fa-check-double',
            'img' => 'appw.png',
            'src' => 'https://img.icons8.com/plasticine/100/workflow.png',
            'sequence' => 15,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // DB::table('modules')->insert([
        //     'code' => 'APP',
        //     'name' => 'Approval',
        //     'description' => 'Request Approval',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'APPW',
        //     'name' => 'Approval Workflow',
        //     'description' => 'Approval Workflow',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'CA',
        //     'name' => 'Cash Advance',
        //     'description' => 'Cash Advance',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'COA',
        //     'name' => 'Chart of Accounts',
        //     'description' => 'Chart of Accounts',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'LIQ',
        //     'name' => 'Liquidation',
        //     'description' => 'Liquidation',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'RFD',
        //     'name' => 'Request for Disbursement',
        //     'description' => 'Request for Disbursement',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'REF',
        //     'name' => 'Refunds',
        //     'description' => 'Refunds',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'REIM',
        //     'name' => 'Reimbursements',
        //     'description' => 'Reimbursements',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'REP',
        //     'name' => 'Reports',
        //     'description' => 'Reports',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'SET',
        //     'name' => 'System Settings',
        //     'description' => 'System Settings',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'UAR',
        //     'name' => 'User Access Rights',
        //     'description' => 'User Access Rights',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'UM',
        //     'name' => 'User Management',
        //     'description' => 'User Management',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('modules')->insert([
        //     'code' => 'UR',
        //     'name' => 'User Roles',
        //     'description' => 'User Roles',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
