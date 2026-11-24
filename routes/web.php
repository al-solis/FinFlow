<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\MainAccountController;
use App\Http\Controllers\AccountCategoryController;
use App\Http\Controllers\AccountSubcategoryController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SegmentCodeController;
use App\Http\Controllers\AccountStructureController;
use App\Http\Controllers\AccountStructureDetailController;
use App\Http\Controllers\TaxTypeController;
use App\Http\Controllers\TaxFormulaController;
use App\Http\Controllers\TaxMasterController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\VendorCategoryController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\RfdHeaderController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\RfdController;
use App\Http\Controllers\DisbursementController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\CashAdvanceDisbursementController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccessRightController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\IncomeStatementController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\TaxReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\InvoiceSeriesController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ArSettingController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CreditMemoController;
use App\Http\Controllers\DebitMemoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'mainDashboard'])->name('mainDashboard');

//Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'mainDashboard'])->name('mainDashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/ap/rfd/approval/{transaction}', [RfdController::class, 'showApproval'])->name('ap.rfd.showApproval');
    Route::post('/approvals/{transaction}/approve', [RfdController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{transaction}/return', [RfdController::class, 'returnToRequester'])->name('approvals.return');
    Route::post('/approvals/{transaction}/reject', [RfdController::class, 'reject'])->name('approvals.reject');

    // ---------- TAX ----------
    Route::prefix('tax')->name('tax.')->group(function () {
        Route::middleware('access:tax.ty')->group(function () {
            Route::get('/tax_type', [TaxTypeController::class, 'index'])->name('ty');
            Route::post('/tax_type', [TaxTypeController::class, 'store'])->name('ty.store');
            Route::put('/tax_type/{id}', [TaxTypeController::class, 'update'])->name('ty.update');
        });
        Route::middleware('access:tax.tf')->group(function () {
            Route::get('/tax_formula', [TaxFormulaController::class, 'index'])->name('tf');
            Route::post('/tax_formula', [TaxFormulaController::class, 'store'])->name('tf.store');
            Route::put('/tax_formula/{id}', [TaxFormulaController::class, 'update'])->name('tf.update');
        });
        Route::middleware('access:tax.tm')->group(function () {
            Route::get('/tax_master', [TaxMasterController::class, 'index'])->name('tm');
            Route::post('/tax_master', [TaxMasterController::class, 'store'])->name('tm.store');
            Route::put('/tax_master/{id}', [TaxMasterController::class, 'update'])->name('tm.update');
        });
    });

    // Route::prefix('gl')->name('gl.')->group(function () {

    //     Route::get('/chart', [MainAccountController::class, 'index'])->name('chart');
    //     Route::post('/chart', [MainAccountController::class, 'store'])->name('chart.store');
    //     Route::put('/chart/{id}', [MainAccountController::class, 'updateChart'])->name('chart.update');

    //     Route::get('/chart/category', [AccountCategoryController::class, 'index'])->name('chart.category.index');
    //     Route::post('/chart/category', [AccountCategoryController::class, 'store'])->name('chart.category.store');
    //     Route::put('/chart/category/{id}', [AccountCategoryController::class, 'update'])->name('chart.category.update');

    //     Route::get('/chart/subcategory', [AccountSubcategoryController::class, 'index'])->name('chart.subcategory.index');
    //     Route::post('/chart/subcategory', [AccountSubcategoryController::class, 'store'])->name('chart.subcategory.store');
    //     Route::put('/chart/subcategory/{id}', [AccountSubcategoryController::class, 'update'])->name('chart.subcategory.update');

    //     Route::get('/chart/segment', [SegmentController::class, 'index'])->name('segments');
    //     Route::post('/chart/segment', [SegmentController::class, 'store'])->name('segments.store');
    //     Route::put('/chart/segment/{id}', [SegmentController::class, 'update'])->name('segments.update');

    //     Route::get('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'index'])->name('segments.segment_account.index');
    //     Route::post('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'store'])->name('segments.segment_account.store');
    //     Route::put('/chart/segment/segment_account/{id}', [SegmentCodeController::class, 'update'])->name('segments.segment_account.update');

    //     // Account Structure Routes - Place more specific routes BEFORE the generic update route
    //     Route::get('/chart/account_structures', [AccountStructureController::class, 'index'])->name('structure');
    //     Route::post('/chart/account_structures', [AccountStructureController::class, 'store'])->name('structure.store');

    //     // Chart of Accounts Routes (more specific)
    //     Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts', [ChartOfAccountController::class, 'index'])
    //         ->name('chart_of_accounts.index');
    //     Route::put('/chart/account_structures/{accountStructure}/chart-of-accounts/{chartAccount}', [ChartOfAccountController::class, 'update'])
    //         ->name('chart_of_accounts.update');
    //     Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts/export', [ChartOfAccountController::class, 'export'])
    //         ->name('chart_of_accounts.export');
    //     Route::post('/chart/account_structures/{accountStructure}/chart-of-accounts/bulk-status', [ChartOfAccountController::class, 'bulkUpdateStatus'])
    //         ->name('chart_of_accounts.bulk_status');

    //     // Sync route
    //     Route::post('/chart/account_structures/{accountStructure}/sync', [AccountStructureController::class, 'sync'])
    //         ->name('structure.sync');

    //     // Account Structure Detail Routes
    //     Route::prefix('chart/account_structure_details')
    //         ->name('chart.account_structure_details.')
    //         ->group(function () {
    //             Route::get('/{accountStructure}', [AccountStructureDetailController::class, 'index'])->name('index');
    //             Route::post('/{accountStructure}', [AccountStructureDetailController::class, 'store'])->name('store');
    //             Route::put('/{accountStructure}/{detail}/move-up', [AccountStructureDetailController::class, 'moveUp'])->name('move-up');
    //             Route::put('/{accountStructure}/{detail}/move-down', [AccountStructureDetailController::class, 'moveDown'])->name('move-down');
    //             Route::delete('/{accountStructure}/{detail}', [AccountStructureDetailController::class, 'destroy'])->name('destroy');
    //         });

    //     // Account Structure Update Route (generic - should be last)
    //     Route::put('/chart/account_structures/{id}', [AccountStructureController::class, 'update'])->name('structure.update');

    //     Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('gr');
    //     Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial');

    //     // Journal Entries
    //     Route::get('/journals', [JournalEntryController::class, 'index'])->name('journal');
    //     Route::get('/journals/create', [JournalEntryController::class, 'create'])->name('journals.create');
    //     Route::post('/journals', [JournalEntryController::class, 'store'])->name('journals.store');
    //     Route::get('/journals/{journal}/edit', [JournalEntryController::class, 'edit'])->name('journals.edit');
    //     Route::put('/journals/{journal}', [JournalEntryController::class, 'update'])->name('journals.update');
    //     Route::delete('/journals/{journal}', [JournalEntryController::class, 'destroy'])->name('journals.destroy');

    //     // Journal Approval
    //     Route::get('/journals/{journal}/approval/{transaction}', [JournalEntryController::class, 'showApproval'])->name('journal.showApproval');
    //     Route::post('/journals/approvals/{transaction}/approve', [JournalEntryController::class, 'approve'])->name('journals.approve');
    //     Route::post('/journals/approvals/{transaction}/return', [JournalEntryController::class, 'returnJournal'])->name('journals.return');
    //     Route::post('/journals/approvals/{transaction}/reject', [JournalEntryController::class, 'rejectJournal'])->name('journals.reject');
    // });

    // ---------- GL ----------
    Route::prefix('gl')->name('gl.')->group(function () {

        Route::middleware('access:gl.chart')->group(function () {
            Route::get('/chart', [MainAccountController::class, 'index'])->name('chart');
            Route::post('/chart', [MainAccountController::class, 'store'])->name('chart.store');
            Route::put('/chart/{id}', [MainAccountController::class, 'updateChart'])->name('chart.update');
        });

        Route::middleware('access:gl.chart.category.index')->group(function () {
            Route::get('/chart/category', [AccountCategoryController::class, 'index'])->name('chart.category.index');
            Route::post('/chart/category', [AccountCategoryController::class, 'store'])->name('chart.category.store');
            Route::put('/chart/category/{id}', [AccountCategoryController::class, 'update'])->name('chart.category.update');
        });

        Route::middleware('access:gl.chart.subcategory.index')->group(function () {
            Route::get('/chart/subcategory', [AccountSubcategoryController::class, 'index'])->name('chart.subcategory.index');
            Route::post('/chart/subcategory', [AccountSubcategoryController::class, 'store'])->name('chart.subcategory.store');
            Route::put('/chart/subcategory/{id}', [AccountSubcategoryController::class, 'update'])->name('chart.subcategory.update');
        });

        Route::middleware('access:gl.segments')->group(function () {
            Route::get('/chart/segment', [SegmentController::class, 'index'])->name('segments');
            Route::post('/chart/segment', [SegmentController::class, 'store'])->name('segments.store');
            Route::put('/chart/segment/{id}', [SegmentController::class, 'update'])->name('segments.update');

            Route::get('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'index'])->name('segments.segment_account.index');
            Route::post('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'store'])->name('segments.segment_account.store');
            Route::put('/chart/segment/segment_account/{id}', [SegmentCodeController::class, 'update'])->name('segments.segment_account.update');
        });

        Route::middleware('access:gl.structure')->group(function () {
            Route::get('/chart/account_structures', [AccountStructureController::class, 'index'])->name('structure');
            Route::post('/chart/account_structures', [AccountStructureController::class, 'store'])->name('structure.store');

            Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart_of_accounts.index');
            Route::put('/chart/account_structures/{accountStructure}/chart-of-accounts/{chartAccount}', [ChartOfAccountController::class, 'update'])->name('chart_of_accounts.update');
            Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts/export', [ChartOfAccountController::class, 'export'])->name('chart_of_accounts.export');
            Route::post('/chart/account_structures/{accountStructure}/chart-of-accounts/bulk-status', [ChartOfAccountController::class, 'bulkUpdateStatus'])->name('chart_of_accounts.bulk_status');

            Route::post('/chart/account_structures/{accountStructure}/sync', [AccountStructureController::class, 'sync'])->name('structure.sync');

            Route::prefix('chart/account_structure_details')->name('chart.account_structure_details.')->group(function () {
                Route::get('/{accountStructure}', [AccountStructureDetailController::class, 'index'])->name('index');
                Route::post('/{accountStructure}', [AccountStructureDetailController::class, 'store'])->name('store');
                Route::put('/{accountStructure}/{detail}/move-up', [AccountStructureDetailController::class, 'moveUp'])->name('move-up');
                Route::put('/{accountStructure}/{detail}/move-down', [AccountStructureDetailController::class, 'moveDown'])->name('move-down');
                Route::delete('/{accountStructure}/{detail}', [AccountStructureDetailController::class, 'destroy'])->name('destroy');
            });

            Route::put('/chart/account_structures/{id}', [AccountStructureController::class, 'update'])->name('structure.update'); // keep last
        });

        Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->middleware('access:gl.gr')->name('gr');
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->middleware('access:gl.trial')->name('trial');

        Route::middleware('access:gl.journal')->group(function () {
            Route::get('/journals', [JournalEntryController::class, 'index'])->name('journal');
            Route::get('/journals/create', [JournalEntryController::class, 'create'])->name('journals.create');
            Route::post('/journals', [JournalEntryController::class, 'store'])->name('journals.store');
            Route::get('/journals/{journal}/edit', [JournalEntryController::class, 'edit'])->name('journals.edit');
            Route::put('/journals/{journal}', [JournalEntryController::class, 'update'])->name('journals.update');
            Route::delete('/journals/{journal}', [JournalEntryController::class, 'destroy'])->name('journals.destroy');
        });

        // Journal approval: approver check in controller, not access rights
        Route::get('/journals/{journal}/approval/{transaction}', [JournalEntryController::class, 'showApproval'])->name('journal.showApproval');
        Route::post('/journals/approvals/{transaction}/approve', [JournalEntryController::class, 'approve'])->name('journals.approve');
        Route::post('/journals/approvals/{transaction}/return', [JournalEntryController::class, 'returnJournal'])->name('journals.return');
        Route::post('/journals/approvals/{transaction}/reject', [JournalEntryController::class, 'rejectJournal'])->name('journals.reject');
    });


    // Route::prefix('admin')->name('admin.')->group(function () {
    //     Route::get('/organization', [OrganizationController::class, 'index'])->name('org');
    //     Route::put('/organization', [OrganizationController::class, 'update'])->name('org.update');

    //     Route::get('/term', [TermController::class, 'index'])->name('terms');
    //     Route::post('/term', [TermController::class, 'store'])->name('terms.store');
    //     Route::put('/term/{term}', [TermController::class, 'update'])->name('terms.update');

    //     Route::get('/payment_method', [PaymentMethodController::class, 'index'])->name('method');
    //     Route::post('/payment_method', [PaymentMethodController::class, 'store'])->name('payment_methods.store');
    //     Route::put('/payment_method/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment_methods.update');

    //     Route::get('/currency', [CurrencyController::class, 'index'])->name('currency');
    //     Route::post('/currency', [CurrencyController::class, 'store'])->name('currency.store');
    //     Route::put('/currency/{currency}', [CurrencyController::class, 'update'])->name('currency.update');

    //     Route::get('/users', [UserController::class, 'index'])->name('user');
    //     Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    //     Route::post('/users', [UserController::class, 'store'])->name('users.store');
    //     Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    //     Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    //     Route::get('/roles', [RoleController::class, 'index'])->name('role');
    //     Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    //     Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    //     Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    //     Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    //     Route::get('/access-rights', [AccessRightController::class, 'index'])->name('access');
    //     Route::put('/access-rights', [AccessRightController::class, 'update'])->name('access.update');
    // });


    // ---------- ADMIN ----------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('access:admin.org')->group(function () {
            Route::get('/organization', [OrganizationController::class, 'index'])->name('org');
            Route::put('/organization', [OrganizationController::class, 'update'])->name('org.update');
        });
        Route::middleware('access:admin.terms')->group(function () {
            Route::get('/term', [TermController::class, 'index'])->name('terms');
            Route::post('/term', [TermController::class, 'store'])->name('terms.store');
            Route::put('/term/{term}', [TermController::class, 'update'])->name('terms.update');
        });
        Route::middleware('access:admin.method')->group(function () {
            Route::get('/payment_method', [PaymentMethodController::class, 'index'])->name('method');
            Route::post('/payment_method', [PaymentMethodController::class, 'store'])->name('payment_methods.store');
            Route::put('/payment_method/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment_methods.update');
        });
        Route::middleware('access:admin.currency')->group(function () {
            Route::get('/currency', [CurrencyController::class, 'index'])->name('currency');
            Route::post('/currency', [CurrencyController::class, 'store'])->name('currency.store');
            Route::put('/currency/{currency}', [CurrencyController::class, 'update'])->name('currency.update');
        });
        Route::middleware('access:admin.user')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('user');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
        Route::middleware('access:admin.role')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('role');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
        Route::middleware('access:admin.access')->group(function () {
            Route::get('/access-rights', [AccessRightController::class, 'index'])->name('access');
            Route::put('/access-rights', [AccessRightController::class, 'update'])->name('access.update');
        });
    });

    // Route::prefix('ap')->name('ap.')->group(function () {

    //     Route::get('/rfd', [RfdController::class, 'index'])->name('rfd');
    //     Route::get('/rfd/create', [RfdController::class, 'create'])->name('rfd.create');
    //     Route::post('/rfd', [RfdController::class, 'store'])->name('rfd.store');
    //     Route::get('/rfd/{rfd}/edit', [RfdController::class, 'edit'])->name('rfd.edit');
    //     Route::put('/rfd/{rfd}', [RfdController::class, 'update'])->name('rfd.update');
    //     Route::get('rfd/attachment/{attachment}/download', [RfdController::class, 'downloadAttachment'])
    //         ->name('rfd.download-attachment');
    //     Route::delete('rfd/attachment/{attachment}', [RfdController::class, 'deleteAttachment'])
    //         ->name('rfd.delete-attachment');

    //     Route::get('/vendor', [VendorController::class, 'index'])->name('vendors');
    //     Route::get('/vendor/create', [VendorController::class, 'create'])->name('vendors.create');
    //     Route::post('/vendor', [VendorController::class, 'store'])->name('vendors.store');
    //     Route::delete('/vendor/{vendor}/attachments/{attachment}', [VendorController::class, 'deleteAttachment'])
    //         ->name('vendors.attachments.delete');

    //     Route::get('/vendor/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
    //     Route::put('/vendor/{vendor}', [VendorController::class, 'update'])->name('vendors.update');


    //     // Vendor Categories
    //     Route::get('/vendor/category', [VendorCategoryController::class, 'index'])->name('categories');
    //     Route::get('/vendor/category/create', [VendorCategoryController::class, 'create'])->name('categories.create');
    //     Route::post('/vendor/category', [VendorCategoryController::class, 'store'])->name('categories.store');
    //     Route::get('/vendor/category/{vendorCategory}/edit', [VendorCategoryController::class, 'edit'])->name('categories.edit');
    //     Route::put('/vendor/category/{vendorCategory}', [VendorCategoryController::class, 'update'])->name('categories.update');
    //     Route::delete('/vendor/category/{vendorCategory}', [VendorCategoryController::class, 'destroy'])->name('categories.destroy');
    // });


    // ---------- AP ----------
    Route::prefix('ap')->name('ap.')->group(function () {
        Route::middleware('access:ap.rfd')->group(function () {
            Route::get('/rfd', [RfdController::class, 'index'])->name('rfd');
            Route::get('/rfd/create', [RfdController::class, 'create'])->name('rfd.create');
            Route::post('/rfd', [RfdController::class, 'store'])->name('rfd.store');
            Route::get('/rfd/{rfd}/edit', [RfdController::class, 'edit'])->name('rfd.edit')->middleware('signed');
            Route::put('/rfd/{rfd}', [RfdController::class, 'update'])->name('rfd.update');
            Route::get('rfd/attachment/{attachment}/download', [RfdController::class, 'downloadAttachment'])->name('rfd.download-attachment');
            Route::delete('rfd/attachment/{attachment}', [RfdController::class, 'deleteAttachment'])->name('rfd.delete-attachment');
        });

        Route::middleware('access:ap.vendors')->group(function () {
            Route::get('/vendor', [VendorController::class, 'index'])->name('vendors');
            Route::get('/vendor/create', [VendorController::class, 'create'])->name('vendors.create');
            Route::post('/vendor', [VendorController::class, 'store'])->name('vendors.store');
            Route::delete('/vendor/{vendor}/attachments/{attachment}', [VendorController::class, 'deleteAttachment'])->name('vendors.attachments.delete');
            Route::get('/vendor/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
            Route::put('/vendor/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        });

        Route::middleware('access:ap.categories')->group(function () {
            Route::get('/vendor/category', [VendorCategoryController::class, 'index'])->name('categories');
            Route::get('/vendor/category/create', [VendorCategoryController::class, 'create'])->name('categories.create');
            Route::post('/vendor/category', [VendorCategoryController::class, 'store'])->name('categories.store');
            Route::get('/vendor/category/{vendorCategory}/edit', [VendorCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/vendor/category/{vendorCategory}', [VendorCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/vendor/category/{vendorCategory}', [VendorCategoryController::class, 'destroy'])->name('categories.destroy');
        });
    });


    // Route::prefix('ar')->group(function () {
    //     Route::get('customers', [CustomerController::class, 'index'])->name('ar.cust');
    //     Route::get('customers/create', [CustomerController::class, 'create'])->name('ar.customers.create');
    //     Route::post('customers', [CustomerController::class, 'store'])->name('ar.customers.store');
    //     Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('ar.customers.edit');
    //     Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('ar.customers.update');

    //     // AR-CATG => ar.catg
    //     Route::get('categories', [CustomerCategoryController::class, 'index'])->name('ar.catg');
    //     Route::get('categories/create', [CustomerCategoryController::class, 'create'])->name('ar.categories.create');
    //     Route::post('categories', [CustomerCategoryController::class, 'store'])->name('ar.categories.store');
    //     Route::get('categories/{category}/edit', [CustomerCategoryController::class, 'edit'])->name('ar.categories.edit');
    //     Route::put('categories/{category}', [CustomerCategoryController::class, 'update'])->name('ar.categories.update');
    //     Route::delete('categories/{category}', [CustomerCategoryController::class, 'destroy'])->name('ar.categories.destroy');
    // });


    // ---------- AR ----------
    Route::prefix('ar')->group(function () {
        Route::middleware('access:ar.cust')->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])->name('ar.cust');
            Route::get('customers/create', [CustomerController::class, 'create'])->name('ar.customers.create');
            Route::post('customers', [CustomerController::class, 'store'])->name('ar.customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('ar.customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('ar.customers.update');
        });
        Route::middleware('access:ar.catg')->group(function () {
            Route::get('categories', [CustomerCategoryController::class, 'index'])->name('ar.catg');
            Route::get('categories/create', [CustomerCategoryController::class, 'create'])->name('ar.categories.create');
            Route::post('categories', [CustomerCategoryController::class, 'store'])->name('ar.categories.store');
            Route::get('categories/{category}/edit', [CustomerCategoryController::class, 'edit'])->name('ar.categories.edit');
            Route::put('categories/{category}', [CustomerCategoryController::class, 'update'])->name('ar.categories.update');
            Route::delete('categories/{category}', [CustomerCategoryController::class, 'destroy'])->name('ar.categories.destroy');
        });

        Route::middleware('access:ar.ser')->group(function () {
            Route::get('series', [InvoiceSeriesController::class, 'index'])->name('ar.ser');
            Route::get('series/create', [InvoiceSeriesController::class, 'create'])->name('ar.series.create');
            Route::post('series', [InvoiceSeriesController::class, 'store'])->name('ar.series.store');
            Route::get('series/{series}/edit', [InvoiceSeriesController::class, 'edit'])->name('ar.series.edit');
            Route::put('series/{series}', [InvoiceSeriesController::class, 'update'])->name('ar.series.update');
            Route::delete('series/{series}', [InvoiceSeriesController::class, 'destroy'])->name('ar.series.destroy');
        });


        Route::middleware('access:ar.inv')->group(function () {
            Route::get('invoices', [InvoiceController::class, 'index'])->name('ar.inv');
            Route::get('invoices/create', [InvoiceController::class, 'create'])->name('ar.invoices.create');
            Route::post('invoices', [InvoiceController::class, 'store'])->name('ar.invoices.store');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('ar.invoices.show');
            Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('ar.invoices.edit');
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('ar.invoices.update');
            Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('ar.invoices.destroy');
            Route::post('invoices/{invoice}/submit', [InvoiceController::class, 'submit'])->name('ar.invoices.submit');
        });

        Route::middleware('access:ar.settings')->group(function () {
            Route::get('settings', [ArSettingController::class, 'edit'])->name('ar.settings');
            Route::put('settings', [ArSettingController::class, 'update'])->name('ar.settings.update');
        });

        Route::middleware('access:ar.col')->group(function () {
            Route::get('collections', [CollectionController::class, 'index'])->name('ar.col');
            Route::get('collections/create', [CollectionController::class, 'create'])->name('ar.collections.create');
            Route::post('collections', [CollectionController::class, 'store'])->name('ar.collections.store');
            Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('ar.collections.show');
            Route::get('collections/{collection}/edit', [CollectionController::class, 'edit'])->name('ar.collections.edit');
            Route::put('collections/{collection}', [CollectionController::class, 'update'])->name('ar.collections.update');
            Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('ar.collections.destroy');
            Route::post('collections/{collection}/submit', [CollectionController::class, 'submit'])->name('ar.collections.submit');
            Route::post('collections/{collection}/void', [CollectionController::class, 'void'])->name('ar.collections.void');
        });

        // Approval actions (approver check in controller, same pattern as invoices)
        Route::get('collections/{collection}/approval/{transaction}', [CollectionController::class, 'showApproval'])->name('ar.collections.showApproval');
        Route::post('collections/approvals/{transaction}/approve', [CollectionController::class, 'approve'])->name('ar.collections.approve');
        Route::post('collections/approvals/{transaction}/return', [CollectionController::class, 'returnCollection'])->name('ar.collections.return');
        Route::post('collections/approvals/{transaction}/reject', [CollectionController::class, 'rejectCollection'])->name('ar.collections.reject');


        // ---------- Credit Memos ----------
        Route::middleware('access:ar.cmemo')->group(function () {
            Route::get('credit-memos', [CreditMemoController::class, 'index'])->name('ar.cmemo');
            Route::get('credit-memos/create', [CreditMemoController::class, 'create'])->name('ar.credit-memos.create');
            Route::post('credit-memos', [CreditMemoController::class, 'store'])->name('ar.credit-memos.store');
            Route::get('credit-memos/invoice-lines/{invoice}', [CreditMemoController::class, 'invoiceLines'])->name('ar.credit-memos.invoice-lines');
            Route::get('credit-memos/{memo}', [CreditMemoController::class, 'show'])->name('ar.credit-memos.show');
            Route::get('credit-memos/{memo}/edit', [CreditMemoController::class, 'edit'])->name('ar.credit-memos.edit');
            Route::put('credit-memos/{memo}', [CreditMemoController::class, 'update'])->name('ar.credit-memos.update');
            Route::delete('credit-memos/{memo}', [CreditMemoController::class, 'destroy'])->name('ar.credit-memos.destroy');
            Route::post('credit-memos/{memo}/submit', [CreditMemoController::class, 'submit'])->name('ar.credit-memos.submit');
            Route::post('credit-memos/{memo}/void', [CreditMemoController::class, 'void'])->name('ar.credit-memos.void');
        });

        // Approval actions: the approver check is in the controller (same pattern as invoices)
        Route::get('credit-memos/{memo}/approval/{transaction}', [CreditMemoController::class, 'showApproval'])->name('ar.credit-memos.showApproval');
        Route::post('credit-memos/approvals/{transaction}/approve', [CreditMemoController::class, 'approve'])->name('ar.credit-memos.approve');
        Route::post('credit-memos/approvals/{transaction}/return', [CreditMemoController::class, 'returnMemo'])->name('ar.credit-memos.return');
        Route::post('credit-memos/approvals/{transaction}/reject', [CreditMemoController::class, 'rejectMemo'])->name('ar.credit-memos.reject');

        // ---------- Debit Memos ----------
        Route::middleware('access:ar.dmemo')->group(function () {
            Route::get('debit-memos', [DebitMemoController::class, 'index'])->name('ar.dmemo');
            Route::get('debit-memos/create', [DebitMemoController::class, 'create'])->name('ar.debit-memos.create');
            Route::post('debit-memos', [DebitMemoController::class, 'store'])->name('ar.debit-memos.store');
            Route::get('debit-memos/invoice-lines/{invoice}', [DebitMemoController::class, 'invoiceLines'])->name('ar.debit-memos.invoice-lines');
            Route::get('debit-memos/{memo}', [DebitMemoController::class, 'show'])->name('ar.debit-memos.show');
            Route::get('debit-memos/{memo}/edit', [DebitMemoController::class, 'edit'])->name('ar.debit-memos.edit');
            Route::put('debit-memos/{memo}', [DebitMemoController::class, 'update'])->name('ar.debit-memos.update');
            Route::delete('debit-memos/{memo}', [DebitMemoController::class, 'destroy'])->name('ar.debit-memos.destroy');
            Route::post('debit-memos/{memo}/submit', [DebitMemoController::class, 'submit'])->name('ar.debit-memos.submit');
            Route::post('debit-memos/{memo}/void', [DebitMemoController::class, 'void'])->name('ar.debit-memos.void');
        });

        // Approval actions: the approver check is in the controller (same pattern as invoices)
        Route::get('debit-memos/{memo}/approval/{transaction}', [DebitMemoController::class, 'showApproval'])->name('ar.debit-memos.showApproval');
        Route::post('debit-memos/approvals/{transaction}/approve', [DebitMemoController::class, 'approve'])->name('ar.debit-memos.approve');
        Route::post('debit-memos/approvals/{transaction}/return', [DebitMemoController::class, 'returnMemo'])->name('ar.debit-memos.return');
        Route::post('debit-memos/approvals/{transaction}/reject', [DebitMemoController::class, 'rejectMemo'])->name('ar.debit-memos.reject');

        // Approval actions: approver check is in the controller, not in access rights,
        // mirroring the journal approval block.
        Route::get('invoices/{invoice}/approval/{transaction}', [InvoiceController::class, 'showApproval'])->name('ar.invoices.showApproval');
        Route::post('invoices/approvals/{transaction}/approve', [InvoiceController::class, 'approve'])->name('ar.invoices.approve');
        Route::post('invoices/approvals/{transaction}/return', [InvoiceController::class, 'returnInvoice'])->name('ar.invoices.return');
        Route::post('invoices/approvals/{transaction}/reject', [InvoiceController::class, 'rejectInvoice'])->name('ar.invoices.reject');
    });

    // Route::prefix('cm')->name('cm.')->group(function () {
    //     Route::get('/disbursement', [DisbursementController::class, 'index'])->name('dv');
    //     // RFD Disbursement
    //     Route::get('/disbursement/rfd/{rfd}', [DisbursementController::class, 'show'])->name('cash.disbursement.show');
    //     Route::post('/disbursement/rfd/{rfd}/disburse', [DisbursementController::class, 'disburse'])->name('cash.disbursement.disburse');

    //     // Cash Advances
    //     Route::get('/ca/', [CashAdvanceController::class, 'index'])->name('ca');
    //     Route::get('/ca/create', [CashAdvanceController::class, 'create'])->name('ca.create');
    //     Route::post('/ca/', [CashAdvanceController::class, 'store'])->name('ca.store');
    //     Route::get('/ca/{cashAdvance}/edit', [CashAdvanceController::class, 'edit'])->name('ca.edit');
    //     Route::put('/ca/{cashAdvance}', [CashAdvanceController::class, 'update'])->name('ca.update');

    //     // Cash Advance Disbursement
    //     Route::get('/disbursement/ca/{cashAdvance}', [DisbursementController::class, 'showCa'])->name('cash.disbursement.show.ca');
    //     Route::post('/disbursement/ca/{cashAdvance}/disburse', [DisbursementController::class, 'disburseCa'])->name('cash.disbursement.disburse.ca');

    //     // Cash Advance Approval
    //     Route::get('/ca/{cashAdvance}/approval/{transaction}', [CashAdvanceController::class, 'showApproval'])->name('ca.showApproval');
    //     Route::post('/ca/approvals/{transaction}/approve', [CashAdvanceController::class, 'approve'])->name('ca.approve');
    //     Route::post('/ca/approvals/{transaction}/return', [CashAdvanceController::class, 'returnToRequester'])->name('ca.return');
    //     Route::post('/ca/approvals/{transaction}/reject', [CashAdvanceController::class, 'reject'])->name('ca.reject');

    //     Route::get('/ca/disbursement', [CashAdvanceDisbursementController::class, 'index'])->name('ca.disbursement');
    //     Route::get('/ca/disbursement/{cashAdvance}', [CashAdvanceDisbursementController::class, 'show'])->name('ca.disbursement.show');
    //     Route::post('/ca/disbursement/{cashAdvance}', [CashAdvanceDisbursementController::class, 'disburse'])->name('ca.disbursement.disburse');

    //     // Liquidations
    //     Route::get('/liquidations', [CashAdvanceController::class, 'liquidationsIndex'])->name('liq');
    //     Route::get('/liquidation/{cashAdvance}/liquidate', [CashAdvanceController::class, 'createLiquidation'])->name('liquidation.create');
    //     Route::post('/liquidation/{cashAdvance}/liquidate', [CashAdvanceController::class, 'storeLiquidation'])->name('liquidation.store');
    //     Route::get('/liquidation/{liquidation}/edit', [CashAdvanceController::class, 'editLiquidation'])->name('liquidation.edit');
    //     Route::put('/liquidation/{liquidation}', [CashAdvanceController::class, 'updateLiquidation'])->name('liquidation.update');
    //     Route::get('/liquidation/{liquidation}/approval/{transaction}', [CashAdvanceController::class, 'showLiquidationApproval'])->name('liquidation.showApproval');
    //     Route::post('/liquidation/approval/{transaction}/approve', [CashAdvanceController::class, 'approveLiquidation'])
    //         ->name('liquidation.approve');
    //     Route::post('/liquidation/approval/{transaction}/return', [CashAdvanceController::class, 'returnLiquidation'])
    //         ->name('liquidation.return');
    //     Route::post('/liquidation/approval/{transaction}/reject', [CashAdvanceController::class, 'rejectLiquidation'])
    //         ->name('liquidation.reject');

    //     // Liquidation Attachments
    //     Route::get('/liquidation/attachment/{attachment}/download', [CashAdvanceController::class, 'downloadLiquidationAttachment'])->name('liquidation.download-attachment');
    //     Route::delete('/liquidation/attachment/{attachment}', [CashAdvanceController::class, 'deleteLiquidationAttachment'])->name('liquidation.delete-attachment');

    //     // Refunds
    //     Route::get('/refunds', [CashAdvanceController::class, 'refundsIndex'])->name('ref');
    //     Route::get('/refund/create/{cashAdvance?}', [CashAdvanceController::class, 'createRefund'])->name('refund.create');
    //     Route::post('/refund', [CashAdvanceController::class, 'storeRefund'])->name('refund.store');
    //     Route::get('/refund/{refund}/edit', [CashAdvanceController::class, 'editRefund'])->name('refund.edit');
    //     Route::put('/refund/{refund}', [CashAdvanceController::class, 'updateRefund'])->name('refund.update');
    //     Route::get('/refund/{refund}/approval/{transaction}', [CashAdvanceController::class, 'showRefundApproval'])->name('refund.showApproval');
    //     Route::post('/refund/approvals/{transaction}/approve', [CashAdvanceController::class, 'approveRefund'])->name('refund.approve');
    //     Route::post('/refund/approvals/{transaction}/return', [CashAdvanceController::class, 'returnRefund'])->name('refund.return');
    //     Route::post('/refund/approvals/{transaction}/reject', [CashAdvanceController::class, 'rejectRefund'])->name('refund.reject');


    //     // Reimbursements
    //     Route::get('/reimbursements', [CashAdvanceController::class, 'reimbursementsIndex'])->name('reim');
    //     Route::get('/reimbursement/create', [CashAdvanceController::class, 'createReimbursement'])->name('reimbursement.create');
    //     Route::post('/reimbursement', [CashAdvanceController::class, 'storeReimbursement'])->name('reimbursement.store');
    //     Route::get('/reimbursement/{reimbursement}/edit', [CashAdvanceController::class, 'editReimbursement'])->name('reimbursement.edit');
    //     Route::put('/reimbursement/{reimbursement}', [CashAdvanceController::class, 'updateReimbursement'])->name('reimbursement.update');
    //     Route::post('/reimbursement/{reimbursement}/submit', [CashAdvanceController::class, 'submitReimbursement'])->name('reimbursement.submit');
    //     Route::get('/reimbursement/{reimbursement}/approval/{transaction}', [CashAdvanceController::class, 'showReimbursementApproval'])->name('reimbursement.showApproval');
    //     Route::post('/reimbursement/approvals/{transaction}/approve', [CashAdvanceController::class, 'approveReimbursement'])->name('reimbursement.approve');
    //     Route::post('/reimbursement/approvals/{transaction}/return', [CashAdvanceController::class, 'returnReimbursement'])->name('reimbursement.return');
    //     Route::post('/reimbursement/approvals/{transaction}/reject', [CashAdvanceController::class, 'rejectReimbursement'])->name('reimbursement.reject');

    //     // Reimbursement Disbursement
    //     Route::get('/disbursement/reimbursement/{reimbursement}', [DisbursementController::class, 'showReimbursement'])->name('cash.disbursement.show.reimbursement');
    //     Route::post('/disbursement/reimbursement/{reimbursement}/disburse', [DisbursementController::class, 'disburseReimbursement'])->name('cash.disbursement.disburse.reimbursement');
    // });


    // ---------- CM ----------
    Route::prefix('cm')->name('cm.')->group(function () {

        // Disbursement (RFD, CA, reimbursement). Disburse POSTs are really "update"
        Route::middleware('access:cm.dv')->group(function () {
            Route::get('/disbursement', [DisbursementController::class, 'index'])->name('dv');
            Route::get('/disbursement/rfd/{rfd}', [DisbursementController::class, 'show'])->name('cash.disbursement.show');
            Route::post('/disbursement/rfd/{rfd}/disburse', [DisbursementController::class, 'disburse'])->name('cash.disbursement.disburse');
            Route::get('/disbursement/ca/{cashAdvance}', [DisbursementController::class, 'showCa'])->name('cash.disbursement.show.ca');
            Route::post('/disbursement/ca/{cashAdvance}/disburse', [DisbursementController::class, 'disburseCa'])->name('cash.disbursement.disburse.ca');
            Route::get('/disbursement/reimbursement/{reimbursement}', [DisbursementController::class, 'showReimbursement'])->name('cash.disbursement.show.reimbursement');
            Route::post('/disbursement/reimbursement/{reimbursement}/disburse', [DisbursementController::class, 'disburseReimbursement'])->name('cash.disbursement.disburse.reimbursement');

            Route::get('/ca/disbursement', [CashAdvanceDisbursementController::class, 'index'])->name('ca.disbursement');
            Route::get('/ca/disbursement/{cashAdvance}', [CashAdvanceDisbursementController::class, 'show'])->name('ca.disbursement.show');
            Route::post('/ca/disbursement/{cashAdvance}', [CashAdvanceDisbursementController::class, 'disburse'])->name('ca.disbursement.disburse');
        });

        // Cash advances
        Route::middleware('access:cm.ca')->group(function () {
            Route::get('/ca/', [CashAdvanceController::class, 'index'])->name('ca');
            Route::get('/ca/create', [CashAdvanceController::class, 'create'])->name('ca.create');
            Route::post('/ca/', [CashAdvanceController::class, 'store'])->name('ca.store');
            Route::get('/ca/{cashAdvance}/edit', [CashAdvanceController::class, 'edit'])->name('ca.edit');
            Route::put('/ca/{cashAdvance}', [CashAdvanceController::class, 'update'])->name('ca.update');
        });

        // Liquidations
        Route::middleware('access:cm.liq')->group(function () {
            Route::get('/liquidations', [CashAdvanceController::class, 'liquidationsIndex'])->name('liq');
            Route::get('/liquidation/{cashAdvance}/liquidate', [CashAdvanceController::class, 'createLiquidation'])->name('liquidation.create');
            Route::post('/liquidation/{cashAdvance}/liquidate', [CashAdvanceController::class, 'storeLiquidation'])->name('liquidation.store');
            Route::get('/liquidation/{liquidation}/edit', [CashAdvanceController::class, 'editLiquidation'])->name('liquidation.edit');
            Route::put('/liquidation/{liquidation}', [CashAdvanceController::class, 'updateLiquidation'])->name('liquidation.update');
            Route::get('/liquidation/attachment/{attachment}/download', [CashAdvanceController::class, 'downloadLiquidationAttachment'])->name('liquidation.download-attachment');
            Route::delete('/liquidation/attachment/{attachment}', [CashAdvanceController::class, 'deleteLiquidationAttachment'])->name('liquidation.delete-attachment');
        });

        // Refunds
        Route::middleware('access:cm.ref')->group(function () {
            Route::get('/refunds', [CashAdvanceController::class, 'refundsIndex'])->name('ref');
            Route::get('/refund/create/{cashAdvance?}', [CashAdvanceController::class, 'createRefund'])->name('refund.create');
            Route::post('/refund', [CashAdvanceController::class, 'storeRefund'])->name('refund.store');
            Route::get('/refund/{refund}/edit', [CashAdvanceController::class, 'editRefund'])->name('refund.edit');
            Route::put('/refund/{refund}', [CashAdvanceController::class, 'updateRefund'])->name('refund.update');
        });

        // Reimbursements
        Route::middleware('access:cm.reim')->group(function () {
            Route::get('/reimbursements', [CashAdvanceController::class, 'reimbursementsIndex'])->name('reim');
            Route::get('/reimbursement/create', [CashAdvanceController::class, 'createReimbursement'])->name('reimbursement.create');
            Route::post('/reimbursement', [CashAdvanceController::class, 'storeReimbursement'])->name('reimbursement.store');
            Route::get('/reimbursement/{reimbursement}/edit', [CashAdvanceController::class, 'editReimbursement'])->name('reimbursement.edit');
            Route::put('/reimbursement/{reimbursement}', [CashAdvanceController::class, 'updateReimbursement'])->name('reimbursement.update');
            Route::post('/reimbursement/{reimbursement}/submit', [CashAdvanceController::class, 'submitReimbursement'])->name('reimbursement.submit');
        });

        // Approvals (CA, liquidation, refund, reimbursement): approver check in controllers
        Route::get('/ca/{cashAdvance}/approval/{transaction}', [CashAdvanceController::class, 'showApproval'])->name('ca.showApproval');
        Route::post('/ca/approvals/{transaction}/approve', [CashAdvanceController::class, 'approve'])->name('ca.approve');
        Route::post('/ca/approvals/{transaction}/return', [CashAdvanceController::class, 'returnToRequester'])->name('ca.return');
        Route::post('/ca/approvals/{transaction}/reject', [CashAdvanceController::class, 'reject'])->name('ca.reject');

        Route::get('/liquidation/{liquidation}/approval/{transaction}', [CashAdvanceController::class, 'showLiquidationApproval'])->name('liquidation.showApproval');
        Route::post('/liquidation/approval/{transaction}/approve', [CashAdvanceController::class, 'approveLiquidation'])->name('liquidation.approve');
        Route::post('/liquidation/approval/{transaction}/return', [CashAdvanceController::class, 'returnLiquidation'])->name('liquidation.return');
        Route::post('/liquidation/approval/{transaction}/reject', [CashAdvanceController::class, 'rejectLiquidation'])->name('liquidation.reject');

        Route::get('/refund/{refund}/approval/{transaction}', [CashAdvanceController::class, 'showRefundApproval'])->name('refund.showApproval');
        Route::post('/refund/approvals/{transaction}/approve', [CashAdvanceController::class, 'approveRefund'])->name('refund.approve');
        Route::post('/refund/approvals/{transaction}/return', [CashAdvanceController::class, 'returnRefund'])->name('refund.return');
        Route::post('/refund/approvals/{transaction}/reject', [CashAdvanceController::class, 'rejectRefund'])->name('refund.reject');

        Route::get('/reimbursement/{reimbursement}/approval/{transaction}', [CashAdvanceController::class, 'showReimbursementApproval'])->name('reimbursement.showApproval');
        Route::post('/reimbursement/approvals/{transaction}/approve', [CashAdvanceController::class, 'approveReimbursement'])->name('reimbursement.approve');
        Route::post('/reimbursement/approvals/{transaction}/return', [CashAdvanceController::class, 'returnReimbursement'])->name('reimbursement.return');
        Route::post('/reimbursement/approvals/{transaction}/reject', [CashAdvanceController::class, 'rejectReimbursement'])->name('reimbursement.reject');
    });

    // Route::prefix('bm')->name('bm.')->group(function () {
    //     Route::get('/bank', [BankAccountController::class, 'index'])->name('bank');
    //     Route::get('/bank/create', [BankAccountController::class, 'create'])->name('bank.create');
    //     Route::post('/bank', [BankAccountController::class, 'store'])->name('bank.store');

    //     Route::get('/bank/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('bank.edit');
    //     Route::put('/bank/{bankAccount}', [BankAccountController::class, 'update'])->name('bank.update');

    //     Route::get('/recon', [BankReconciliationController::class, 'index'])->name('recon');
    //     Route::post('/recon/upload', [BankReconciliationController::class, 'upload'])->name('recon.upload');
    //     Route::get('/recon/{import}', [BankReconciliationController::class, 'review'])->name('recon.review');
    //     Route::post('/recon/{import}/post', [BankReconciliationController::class, 'post'])->name('recon.post');

    // });


    // ---------- BM ----------
    Route::prefix('bm')->name('bm.')->group(function () {
        Route::middleware('access:bm.bank')->group(function () {
            Route::get('/bank', [BankAccountController::class, 'index'])->name('bank');
            Route::get('/bank/create', [BankAccountController::class, 'create'])->name('bank.create');
            Route::post('/bank', [BankAccountController::class, 'store'])->name('bank.store');
            Route::get('/bank/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('bank.edit');
            Route::put('/bank/{bankAccount}', [BankAccountController::class, 'update'])->name('bank.update');
        });
        Route::middleware('access:bm.recon')->group(function () {
            Route::get('/recon', [BankReconciliationController::class, 'index'])->name('recon');
            Route::post('/recon/upload', [BankReconciliationController::class, 'upload'])->name('recon.upload');
            Route::get('/recon/{import}', [BankReconciliationController::class, 'review'])->name('recon.review');
            Route::post('/recon/{import}/post', [BankReconciliationController::class, 'post'])->name('recon.post');
        });
    });

    // Route::prefix('appw')->name('appw.')->group(function () {
    //     Route::get('/approval_workflows', [ApprovalWorkflowController::class, 'index'])->name('appr');
    //     Route::get('/approval_workflows/create', [ApprovalWorkflowController::class, 'create'])->name('create');
    //     Route::post('/approval_workflows', [ApprovalWorkflowController::class, 'store'])->name('store');
    //     Route::get('/approval_workflows/{approval_workflow}/edit', [ApprovalWorkflowController::class, 'edit'])->name('edit');
    //     Route::put('/approval_workflows/{approval_workflow}', [ApprovalWorkflowController::class, 'update'])->name('update');
    //     Route::patch('/approval_workflows/{approval_workflow}/toggle-status', [ApprovalWorkflowController::class, 'toggleStatus'])
    //         ->name('toggleStatus');
    // });

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // ---------- Approval workflows setup ----------
    Route::prefix('appw')->name('appw.')->middleware('access:appw.appr')->group(function () {
        Route::get('/approval_workflows', [ApprovalWorkflowController::class, 'index'])->name('appr');
        Route::get('/approval_workflows/create', [ApprovalWorkflowController::class, 'create'])->name('create');
        Route::post('/approval_workflows', [ApprovalWorkflowController::class, 'store'])->name('store');
        Route::get('/approval_workflows/{approval_workflow}/edit', [ApprovalWorkflowController::class, 'edit'])->name('edit');
        Route::put('/approval_workflows/{approval_workflow}', [ApprovalWorkflowController::class, 'update'])->name('update');
        Route::patch('/approval_workflows/{approval_workflow}/toggle-status', [ApprovalWorkflowController::class, 'toggleStatus'])->name('toggleStatus');
    });


    // Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    // Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    // Route::get('/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    // Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');

    // Balance Sheet (dedicated)
    //Route::get('/fin/balance-sheet', [BalanceSheetController::class, 'index'])->name('fin.balance-sheet');

    // Income Statement (dedicated)
    //Route::get('/fin/income-statement', [IncomeStatementController::class, 'index'])->name('fin.income-statement');

    // Report Export
    //Route::get('/reports/export', [ReportExportController::class, 'export'])->name('reports.export');

    // Tax Report Routes
    // Route::prefix('reports')->name('reports.')->group(function () {
    //     Route::get('/tax', [TaxReportController::class, 'index'])->name('tax');
    //     Route::get('/tax/export', [TaxReportController::class, 'export'])->name('tax.export');
    //     Route::get('/tax/generate', [TaxReportController::class, 'generate'])->name('tax.generate');
    // });

    // Also add to FIN module routes
    //Route::get('/fin/tax-report', [TaxReportController::class, 'index'])->name('fin.tax');

    // Dedicated Report Pages
    // Route::get('/fin/balance-sheet', [BalanceSheetController::class, 'index'])->name('fin.bs');
    // Route::get('/fin/income-statement', [IncomeStatementController::class, 'index'])->name('fin.is');
    // Route::get('/fin/cash-flow', [ReportController::class, 'cashFlow'])->name('fin.cf');

    // Quick Reports from Dashboard
    // Route::get('/reports/quick/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.quick.trial-balance');
    // Route::get('/reports/quick/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.quick.balance-sheet');
    // Route::get('/reports/quick/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.quick.income-statement');

    // ---------- Reports / FIN ----------
    Route::get('/reports', [ReportController::class, 'index'])->middleware('access:reports.index')->name('reports.index');
    Route::get('/reports/export', [ReportExportController::class, 'export'])->middleware('access:reports.index')->name('reports.export');

    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->middleware('access:gl.trial')->name('reports.trial-balance');
    Route::get('/reports/quick/trial-balance', [ReportController::class, 'trialBalance'])->middleware('access:gl.trial')->name('reports.quick.trial-balance');

    Route::middleware('access:fin.bs')->group(function () {
        Route::get('/fin/balance-sheet', [BalanceSheetController::class, 'index'])->name('fin.bs');
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/quick/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.quick.balance-sheet');
    });

    Route::middleware('access:fin.is')->group(function () {
        Route::get('/fin/income-statement', [IncomeStatementController::class, 'index'])->name('fin.is');
        Route::get('/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('/reports/quick/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.quick.income-statement');
    });

    Route::middleware('access:fin.cf')->group(function () {
        Route::get('/fin/cash-flow', [ReportController::class, 'cashFlow'])->name('fin.cf');
        Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    });

    Route::middleware('access:fin.tax')->group(function () {
        Route::get('/fin/tax-report', [TaxReportController::class, 'index'])->name('fin.tax');
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/tax', [TaxReportController::class, 'index'])->name('tax');
            Route::get('/tax/export', [TaxReportController::class, 'export'])->name('tax.export');
            Route::get('/tax/generate', [TaxReportController::class, 'generate'])->name('tax.generate');
        });
    });

    //MASTER
    Route::prefix('master')->group(function () {

        Route::get('uoms', [UnitOfMeasureController::class, 'index'])->name('master.uom');
        Route::get('uoms/create', [UnitOfMeasureController::class, 'create'])->name('master.uoms.create');
        Route::post('uoms', [UnitOfMeasureController::class, 'store'])->name('master.uoms.store');
        Route::get('uoms/{uom}/edit', [UnitOfMeasureController::class, 'edit'])->name('master.uoms.edit');
        Route::put('uoms/{uom}', [UnitOfMeasureController::class, 'update'])->name('master.uoms.update');
        Route::delete('uoms/{uom}', [UnitOfMeasureController::class, 'destroy'])->name('master.uoms.destroy');

        Route::get('items', [ItemController::class, 'index'])->name('master.item');
        Route::get('items/lookup', [ItemController::class, 'lookup'])->name('master.items.lookup'); // keep above {item}
        Route::get('items/create', [ItemController::class, 'create'])->name('master.items.create');
        Route::post('items', [ItemController::class, 'store'])->name('master.items.store');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('master.items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('master.items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('master.items.destroy');
    });
});

require __DIR__ . '/auth.php';
