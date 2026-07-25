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
    });

});

require __DIR__ . '/auth.php';
