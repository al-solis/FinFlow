<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use App\View\Composers\DashboardComposer;
use App\Services\PiiMaskingService;
use Illuminate\Support\Facades\Auth;
use App\Models\approval_transaction;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('pii.masker', function ($app) {
            return new PiiMaskingService(Auth::user());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', DashboardComposer::class);

        // PII Masking Blade Directive
        Blade::directive('maskpii', function ($expression) {
            return "<?php echo app('pii.masker')->mask({$expression}); ?>";
        });

        // Conditional PII Display
        Blade::directive('ifFullAccess', function ($expression) {
            return "<?php if (app('pii.masker')->hasFullAccess()): ?>";
        });

        Blade::directive('endIfFullAccess', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('ifPartialAccess', function ($expression) {
            return "<?php if (app('pii.masker')->hasPartialAccess()): ?>";
        });

        Blade::directive('endIfPartialAccess', function () {
            return "<?php endif; ?>";
        });

        // Access indicator badge
        Blade::directive('piiAccessBadge', function () {
            return "<?php 
                \$masker = app('pii.masker');
                if (\$masker->hasFullAccess()) {
                    echo '<span class=\"inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800\"><svg class=\"mr-1 h-3 w-3\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z\" clip-rule=\"evenodd\"/></svg>Full Access</span>';
                } elseif (\$masker->hasPartialAccess()) {
                    echo '<span class=\"inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800\"><svg class=\"mr-1 h-3 w-3\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.334-.213 2.98-1.742 2.98H3.48c-1.53 0-2.492-1.646-1.743-2.98l6.518-11.59zM10 13a1 1 0 100-2 1 1 0 000 2zm-1-8a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1z\" clip-rule=\"evenodd\"/></svg>Limited Access</span>';
                } else {
                    echo '<span class=\"inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600\"><svg class=\"mr-1 h-3 w-3\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z\" clip-rule=\"evenodd\"/></svg>Restricted</span>';
                }
            ?>";
        });

        // Mask level badge directive
        Blade::directive('maskLevelBadge', function () {
            return "<?php echo app('pii.masker')->getMaskLevelBadge(); ?>";
        });

        View::composer('*', function ($view) {
            if (Auth::check()) {
                $pendingApprovals = approval_transaction::pendingFor(Auth::user())->count();
                $view->with('globalPendingApprovals', $pendingApprovals);
            }
        });
    }
}
