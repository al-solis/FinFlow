<?php
// app/Constants/Modules.php

namespace App\Constants;

class Modules
{
    // ==================== MAIN MODULES ====================

    public const GL = 'GL';
    public const AP = 'AP';
    public const AR = 'AR';
    public const CM = 'CM';
    public const BM = 'BM';
    public const FA = 'FA';
    public const IA = 'IA';
    public const PUR = 'PUR';
    public const SALES = 'SALES';
    public const BUDGET = 'BUDGET';
    public const TAX = 'TAX';
    public const COST = 'COST';
    public const FIN = 'FIN';
    public const MASTER = 'MASTER';
    public const ADMIN = 'ADMIN';
    public const APPW = 'APPW';

    // ==================== SUB-MODULES ====================

    // AP Sub-modules
    public const AP_VENDORS = 'AP-VENDORS';
    public const AP_CATEGORIES = 'AP-CATEGORIES';
    public const AP_RFD = 'AP-RFD';
    public const AP_PI = 'AP-PI';
    public const AP_DM = 'AP-DM';
    public const AP_CM = 'AP-CM';
    public const AP_PV = 'AP-PV';

    // CM Sub-modules
    public const CM_CR = 'CM-CR';
    public const CM_DV = 'CM-DV';
    public const CM_PC = 'CM-PC';
    public const CM_CA = 'CM-CA';
    public const CM_LIQ = 'CM-LIQ';
    public const CM_REF = 'CM-REF';
    public const CM_REIM = 'CM-REIM';

    // GL Sub-modules
    public const GL_CHART = 'GL-CHART';
    public const GL_SEGMENTS = 'GL-SEGMENTS';
    public const GL_STRUCTURE = 'GL-STRUCTURE';
    public const GL_FISCAL = 'GL-FISCAL';
    public const GL_PERIODS = 'GL-PERIODS';
    public const GL_JOURNAL = 'GL-JOURNAL';
    public const GL_TRIAL = 'GL-TRIAL';
    public const GL_GR = 'GL-GR';

    // BM Sub-modules
    public const BM_RECON = 'BM-RECON';
    public const BM_BANK = 'BM-BANK';

    // TAX Sub-modules
    public const TAX_TY = 'TAX-TY';
    public const TAX_RATE = 'TAX-RATE';
    public const TAX_TF = 'TAX-TF';
    public const TAX_TM = 'TAX-TM';
    public const TAX_TG = 'TAX-TG';

    // FIN Sub-modules
    public const FIN_TB = 'FIN-TB';
    public const FIN_BS = 'FIN-BS';
    public const FIN_IS = 'FIN-IS';
    public const FIN_CF = 'FIN-CF';
    public const FIN_GL = 'FIN-GL';
    public const FIN_TAXR = 'FIN-TAXR';

    // ADMIN Sub-modules
    public const ADMIN_USER = 'ADMIN-USER';
    public const ADMIN_ROLE = 'ADMIN-ROLE';
    public const ADMIN_ACCESS = 'ADMIN-ACCESS';
    public const ADMIN_ORG = 'ADMIN-ORG';
    public const ADMIN_CURRENCY = 'ADMIN-CURRENCY';
    public const ADMIN_TERMS = 'ADMIN-TERMS';
    public const ADMIN_METHOD = 'ADMIN-METHOD';
    public const ADMIN_NUM = 'ADMIN-NUM';

    // APPW Sub-modules
    public const APPW_APPR = 'APPW-APPR';

    // ==================== PERMISSIONS ====================

    public const PERMISSION_CREATE = 'can_create';
    public const PERMISSION_READ = 'can_read';
    public const PERMISSION_UPDATE = 'can_update';
    public const PERMISSION_DELETE = 'can_delete';

    /**
     * Get module label
     */
    public static function getModuleLabel(string $moduleCode): string
    {
        return match ($moduleCode) {
            self::GL => 'General Ledger',
            self::AP => 'Accounts Payable',
            self::AR => 'Accounts Receivable',
            self::CM => 'Cash Management',
            self::BM => 'Bank Management',
            self::FA => 'Fixed Assets',
            self::IA => 'Inventory Accounting',
            self::PUR => 'Purchasing',
            self::SALES => 'Sales',
            self::BUDGET => 'Budget Management',
            self::TAX => 'Tax Management',
            self::COST => 'Cost Accounting',
            self::FIN => 'Financial Reporting',
            self::MASTER => 'Master Files',
            self::ADMIN => 'System Administration',
            self::APPW => 'Approval Workflow',
            default => $moduleCode,
        };
    }

    /**
     * Get sub-module label
     */
    public static function getSubModuleLabel(string $subModuleCode): string
    {
        return match ($subModuleCode) {
                // AP
            self::AP_VENDORS => 'Vendor Master',
            self::AP_CATEGORIES => 'Vendor Categories',
            self::AP_RFD => 'Request for Disbursement',
            self::AP_PI => 'Purchase Invoice',
            self::AP_DM => 'Debit Memo',
            self::AP_CM => 'Credit Memo',
            self::AP_PV => 'Payment Voucher',
                // CM
            self::CM_CR => 'Cash Receipt',
            self::CM_DV => 'Disbursement',
            self::CM_PC => 'Petty Cash',
            self::CM_CA => 'Cash Advance',
            self::CM_LIQ => 'Liquidation',
            self::CM_REF => 'Refund',
            self::CM_REIM => 'Reimbursement',
                // GL
            self::GL_CHART => 'Chart of Accounts',
            self::GL_SEGMENTS => 'Account Segments',
            self::GL_STRUCTURE => 'Account Structure',
            self::GL_FISCAL => 'Fiscal Year Management',
            self::GL_PERIODS => 'Accounting Periods',
            self::GL_JOURNAL => 'Journal Entry',
            self::GL_TRIAL => 'Trial Balance',
            self::GL_GR => 'General Ledger',
                // BM
            self::BM_RECON => 'Bank Reconciliation',
            self::BM_BANK => 'Bank Accounts',
                // TAX
            self::TAX_TY => 'Tax Types',
            self::TAX_RATE => 'Tax Rates',
            self::TAX_TF => 'Tax Formula',
            self::TAX_TM => 'Tax Master',
            self::TAX_TG => 'Tax Group',
                // FIN
            self::FIN_TB => 'Trial Balance',
            self::FIN_BS => 'Balance Sheet',
            self::FIN_IS => 'Income Statement',
            self::FIN_CF => 'Cash Flow Statement',
            self::FIN_GL => 'General Ledger',
            self::FIN_TAXR => 'Tax Report',
                // ADMIN
            self::ADMIN_USER => 'User',
            self::ADMIN_ROLE => 'User Role',
            self::ADMIN_ACCESS => 'Access Rights',
            self::ADMIN_ORG => 'Organization',
            self::ADMIN_CURRENCY => 'Currency',
            self::ADMIN_TERMS => 'Payment Terms',
            self::ADMIN_METHOD => 'Payment Methods',
            self::ADMIN_NUM => 'Number Series',
                // APPW
            self::APPW_APPR => 'Approval Workflow',
            default => $subModuleCode,
        };
    }
}