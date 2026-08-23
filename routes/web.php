<?php

use App\Http\Controllers\ProfileController;
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


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    // Route::get('/setup/chart', [MainAccountController::class, 'index'])->name('setup.chart.index');
    // Route::post('/setup/chart', [MainAccountController::class, 'store'])->name('setup.chart.store');
    // Route::put('/setup/chart/{id}', [MainAccountController::class, 'updateChart'])->name('setup.chart.update');
    // Route::get('/setup/chart/category', [AccountCategoryController::class, 'index'])->name('setup.chart.category.index');
    // Route::post('/setup/chart/category', [AccountCategoryController::class, 'store'])->name('setup.chart.category.store');
    // Route::put('/setup/chart/category/{id}', [AccountCategoryController::class, 'update'])->name('setup.chart.category.update');
    // Route::get('/setup/chart/subcategory', [AccountSubcategoryController::class, 'index'])->name('setup.chart.subcategory.index');
    // Route::post('/setup/chart/subcategory', [AccountSubcategoryController::class, 'store'])->name('setup.chart.subcategory.store');
    // Route::put('/setup/chart/subcategory/{id}', [AccountSubcategoryController::class, 'update'])->name('setup.chart.subcategory.update');
    // Route::get('/setup/chart/segment', [SegmentController::class, 'index'])->name('setup.chart.segment.index');
    // Route::post('/setup/chart/segment', [SegmentController::class, 'store'])->name('setup.chart.segment.store');
    // Route::put('/setup/chart/segment/{id}', [SegmentController::class, 'update'])->name('setup.chart.segment.update');
    // Route::get('/setup/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'index'])->name('setup.chart.segment.segment_account.index');
    // Route::post('/setup/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'store'])->name('setup.chart.segment.segment_account.store');
    // Route::put('/setup/chart/segment/segment_account/{id}', [SegmentCodeController::class, 'update'])->name('setup.chart.segment.segment_account.update');
    // Route::get('/setup/chart/account_structures', [AccountStructureController::class, 'index'])->name('setup.chart.account_structures.index');
    // Route::post('/setup/chart/account_structures', [AccountStructureController::class, 'store'])->name('setup.chart.account_structures.store');
    // Route::put('/setup/chart/account_structures/{id}', [AccountStructureController::class, 'update'])->name('setup.chart.account_structures.update');
    // Route::prefix('setup/chart/account_structure_details')
    //     ->name('setup.chart.account_structure_details.')
    //     ->group(function () {
    //         Route::get('/{accountStructure}', [AccountStructureDetailController::class, 'index'])->name('index');
    //         Route::post('/{accountStructure}', [AccountStructureDetailController::class, 'store'])->name('store');
    //         Route::put('/{accountStructure}/{detail}/move-up', [AccountStructureDetailController::class, 'moveUp'])->name('move-up');
    //         Route::put('/{accountStructure}/{detail}/move-down', [AccountStructureDetailController::class, 'moveDown'])->name('move-down');
    //         Route::delete('/{accountStructure}/{detail}', [AccountStructureDetailController::class, 'destroy'])->name('destroy');
    //     });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/ap/rfd/approval/{transaction}', [RfdController::class, 'showApproval'])->name('ap.rfd.showApproval');
    Route::post('/approvals/{transaction}/approve', [RfdController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{transaction}/return', [RfdController::class, 'returnToRequester'])->name('approvals.return');
    Route::post('/approvals/{transaction}/reject', [RfdController::class, 'reject'])->name('approvals.reject');

    Route::prefix('tax')->name('tax.')->group(function () {
        Route::get('/tax_type', [TaxTypeController::class, 'index'])->name('ty');
        Route::post('/tax_type', [TaxTypeController::class, 'store'])->name('ty.store');
        Route::put('/tax_type/{id}', [TaxTypeController::class, 'update'])->name('ty.update');

        Route::get('/tax_formula', [TaxFormulaController::class, 'index'])->name('tf');
        Route::post('/tax_formula', [TaxFormulaController::class, 'store'])->name('tf.store');
        Route::put('/tax_formula/{id}', [TaxFormulaController::class, 'update'])->name('tf.update');

        Route::get('/tax_master', [TaxMasterController::class, 'index'])->name('tm');
        Route::post('/tax_master', [TaxMasterController::class, 'store'])->name('tm.store');
        Route::put('/tax_master/{id}', [TaxMasterController::class, 'update'])->name('tm.update');
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

    //     Route::get('/chart/account_structures', [AccountStructureController::class, 'index'])->name('structure');
    //     Route::post('/chart/account_structures', [AccountStructureController::class, 'store'])->name('structure.store');
    //     Route::post('/chart/account_structures/{accountStructure}/sync', [AccountStructureController::class, 'sync'])
    //         ->name('structure.sync');

    //     //chart display
    //     Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts', [ChartOfAccountController::class, 'index'])
    //         ->name('chart_of_accounts.index');
    //     Route::put('/chart/account_structures/{accountStructure}/chart-of-accounts/{chartAccount}', [ChartOfAccountController::class, 'update'])
    //         ->name('chart_of_accounts.update');
    //     Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts/export', [ChartOfAccountController::class, 'export'])
    //         ->name('chart_of_accounts.export');
    //     Route::post('/chart/account_structures/{accountStructure}/chart-of-accounts/bulk-status', [ChartOfAccountController::class, 'bulkUpdateStatus'])
    //         ->name('chart_of_accounts.bulk_status');

    //     Route::put('/chart/account_structures/{id}', [AccountStructureController::class, 'update'])->name('structure.update');
    //     Route::prefix('chart/account_structure_details')
    //         ->name('chart.account_structure_details.')
    //         ->group(function () {
    //             Route::get('/{accountStructure}', [AccountStructureDetailController::class, 'index'])->name('index');
    //             Route::post('/{accountStructure}', [AccountStructureDetailController::class, 'store'])->name('store');
    //             Route::put('/{accountStructure}/{detail}/move-up', [AccountStructureDetailController::class, 'moveUp'])->name('move-up');
    //             Route::put('/{accountStructure}/{detail}/move-down', [AccountStructureDetailController::class, 'moveDown'])->name('move-down');
    //             Route::delete('/{accountStructure}/{detail}', [AccountStructureDetailController::class, 'destroy'])->name('destroy');
    //         });

    // });

    Route::prefix('gl')->name('gl.')->group(function () {

        Route::get('/chart', [MainAccountController::class, 'index'])->name('chart');
        Route::post('/chart', [MainAccountController::class, 'store'])->name('chart.store');
        Route::put('/chart/{id}', [MainAccountController::class, 'updateChart'])->name('chart.update');

        Route::get('/chart/category', [AccountCategoryController::class, 'index'])->name('chart.category.index');
        Route::post('/chart/category', [AccountCategoryController::class, 'store'])->name('chart.category.store');
        Route::put('/chart/category/{id}', [AccountCategoryController::class, 'update'])->name('chart.category.update');

        Route::get('/chart/subcategory', [AccountSubcategoryController::class, 'index'])->name('chart.subcategory.index');
        Route::post('/chart/subcategory', [AccountSubcategoryController::class, 'store'])->name('chart.subcategory.store');
        Route::put('/chart/subcategory/{id}', [AccountSubcategoryController::class, 'update'])->name('chart.subcategory.update');

        Route::get('/chart/segment', [SegmentController::class, 'index'])->name('segments');
        Route::post('/chart/segment', [SegmentController::class, 'store'])->name('segments.store');
        Route::put('/chart/segment/{id}', [SegmentController::class, 'update'])->name('segments.update');

        Route::get('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'index'])->name('segments.segment_account.index');
        Route::post('/chart/segment/segment_account/{segmentId}', [SegmentCodeController::class, 'store'])->name('segments.segment_account.store');
        Route::put('/chart/segment/segment_account/{id}', [SegmentCodeController::class, 'update'])->name('segments.segment_account.update');

        // Account Structure Routes - Place more specific routes BEFORE the generic update route
        Route::get('/chart/account_structures', [AccountStructureController::class, 'index'])->name('structure');
        Route::post('/chart/account_structures', [AccountStructureController::class, 'store'])->name('structure.store');

        // Chart of Accounts Routes (more specific)
        Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts', [ChartOfAccountController::class, 'index'])
            ->name('chart_of_accounts.index');
        Route::put('/chart/account_structures/{accountStructure}/chart-of-accounts/{chartAccount}', [ChartOfAccountController::class, 'update'])
            ->name('chart_of_accounts.update');
        Route::get('/chart/account_structures/{accountStructure}/chart-of-accounts/export', [ChartOfAccountController::class, 'export'])
            ->name('chart_of_accounts.export');
        Route::post('/chart/account_structures/{accountStructure}/chart-of-accounts/bulk-status', [ChartOfAccountController::class, 'bulkUpdateStatus'])
            ->name('chart_of_accounts.bulk_status');

        // Sync route
        Route::post('/chart/account_structures/{accountStructure}/sync', [AccountStructureController::class, 'sync'])
            ->name('structure.sync');

        // Account Structure Detail Routes
        Route::prefix('chart/account_structure_details')
            ->name('chart.account_structure_details.')
            ->group(function () {
                Route::get('/{accountStructure}', [AccountStructureDetailController::class, 'index'])->name('index');
                Route::post('/{accountStructure}', [AccountStructureDetailController::class, 'store'])->name('store');
                Route::put('/{accountStructure}/{detail}/move-up', [AccountStructureDetailController::class, 'moveUp'])->name('move-up');
                Route::put('/{accountStructure}/{detail}/move-down', [AccountStructureDetailController::class, 'moveDown'])->name('move-down');
                Route::delete('/{accountStructure}/{detail}', [AccountStructureDetailController::class, 'destroy'])->name('destroy');
            });

        // Account Structure Update Route (generic - should be last)
        Route::put('/chart/account_structures/{id}', [AccountStructureController::class, 'update'])->name('structure.update');

        Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('gr');
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/organization', [OrganizationController::class, 'index'])->name('org');
        Route::put('/organization', [OrganizationController::class, 'update'])->name('org.update');

        Route::get('/term', [TermController::class, 'index'])->name('terms');
        Route::post('/term', [TermController::class, 'store'])->name('terms.store');
        Route::put('/term/{term}', [TermController::class, 'update'])->name('terms.update');

        Route::get('/payment_method', [PaymentMethodController::class, 'index'])->name('method');
        Route::post('/payment_method', [PaymentMethodController::class, 'store'])->name('payment_methods.store');
        Route::put('/payment_method/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment_methods.update');

        Route::get('/currency', [CurrencyController::class, 'index'])->name('currency');
        Route::post('/currency', [CurrencyController::class, 'store'])->name('currency.store');
        Route::put('/currency/{currency}', [CurrencyController::class, 'update'])->name('currency.update');
    });

    Route::prefix('ap')->name('ap.')->group(function () {

        Route::get('/rfd', [RfdController::class, 'index'])->name('rfd');
        Route::get('/rfd/create', [RfdController::class, 'create'])->name('rfd.create');
        Route::post('/rfd', [RfdController::class, 'store'])->name('rfd.store');
        Route::get('/rfd/{rfd}/edit', [RfdController::class, 'edit'])->name('rfd.edit');
        Route::put('/rfd/{rfd}', [RfdController::class, 'update'])->name('rfd.update');
        Route::get('rfd/attachment/{attachment}/download', [RfdController::class, 'downloadAttachment'])
            ->name('rfd.download-attachment');
        Route::delete('rfd/attachment/{attachment}', [RfdController::class, 'deleteAttachment'])
            ->name('rfd.delete-attachment');

        Route::get('/vendor', [VendorController::class, 'index'])->name('vendors');
        Route::get('/vendor/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendor', [VendorController::class, 'store'])->name('vendors.store');
        Route::delete('/vendor/{vendor}/attachments/{attachment}', [VendorController::class, 'deleteAttachment'])
            ->name('vendors.attachments.delete');

        Route::get('/vendor/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendor/{vendor}', [VendorController::class, 'update'])->name('vendors.update');


        Route::get('/vendor/category', [VendorCategoryController::class, 'index'])->name('categories');
        Route::post('/vendor/category', [VendorCategoryController::class, 'store'])->name('categories.store');
        Route::put('/vendor/category/{vendorCategory}', [VendorCategoryController::class, 'update'])->name('categories.update');
    });

    Route::prefix('cm')->name('cm.')->group(function () {
        Route::get('/disbursement', [DisbursementController::class, 'index'])->name('dv');
        Route::get('/disbursement/{rfd}', [DisbursementController::class, 'show'])->name('cash.disbursement.show');
        Route::post('/disbursement/{rfd}/disburse', [DisbursementController::class, 'disburse'])->name('cash.disbursement.disburse');
    });

    Route::prefix('bm')->name('bm.')->group(function () {
        Route::get('/bank', [BankAccountController::class, 'index'])->name('bank');
        Route::get('/bank/create', [BankAccountController::class, 'create'])->name('bank.create');
        Route::post('/bank', [BankAccountController::class, 'store'])->name('bank.store');

        Route::get('/bank/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('bank.edit');
        Route::put('/bank/{bankAccount}', [BankAccountController::class, 'update'])->name('bank.update');

        Route::get('/recon', [BankReconciliationController::class, 'index'])->name('recon');
        Route::post('/recon/upload', [BankReconciliationController::class, 'upload'])->name('recon.upload');
        Route::get('/recon/{import}', [BankReconciliationController::class, 'review'])->name('recon.review');
        Route::post('/recon/{import}/post', [BankReconciliationController::class, 'post'])->name('recon.post');

    });

    Route::prefix('appw')->name('appw.')->group(function () {
        Route::get('/approval_workflows', [ApprovalWorkflowController::class, 'index'])->name('appr');
        Route::get('/approval_workflows/create', [ApprovalWorkflowController::class, 'create'])->name('create');
        Route::post('/approval_workflows', [ApprovalWorkflowController::class, 'store'])->name('store');
        Route::get('/approval_workflows/{approval_workflow}/edit', [ApprovalWorkflowController::class, 'edit'])->name('edit');
        Route::put('/approval_workflows/{approval_workflow}', [ApprovalWorkflowController::class, 'update'])->name('update');
    });


});

require __DIR__ . '/auth.php';
