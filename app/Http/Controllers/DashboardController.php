<?php
namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Models\module;
use App\Models\approval_transaction;
use App\Models\rfd_header;
use App\Models\CashAdvance;
use App\Models\gl_journal;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $authService = new AuthorizationService($user);

        // Get all active modules with sub-modules
        $allModules = module::with([
            'subModules' => function ($query) {
                $query->where('is_active', true);
            }
        ])
            ->where('is_active', true)
            ->orderBy('sequence')
            ->get();

        // Filter modules - only keep those with accessible sub-modules
        $modules = $allModules->filter(function ($module) use ($authService) {
            // Check if any sub-module in this module is accessible
            foreach ($module->subModules as $subModule) {
                $permissions = $authService->getPermissions($module->code, $subModule->code);
                $hasAccess = $permissions['can_read'] || $permissions['can_create'] ||
                    $permissions['can_update'] || $permissions['can_delete'];

                if ($hasAccess && Route::has($subModule->route_name)) {
                    return true; // Module has at least one accessible sub-module
                }
            }
            return false; // No accessible sub-modules
        })->values();

        // Attach permissions to each sub-module
        $modules = $modules->map(function ($module) use ($authService) {
            $module->subModules = $module->subModules->filter(function ($subModule) use ($authService, $module) {
                $permissions = $authService->getPermissions($module->code, $subModule->code);
                $subModule->can_create = $permissions['can_create'];
                $subModule->can_read = $permissions['can_read'];
                $subModule->can_update = $permissions['can_update'];
                $subModule->can_delete = $permissions['can_delete'];
                $subModule->has_access = $permissions['can_read'] || $permissions['can_create'] ||
                    $permissions['can_update'] || $permissions['can_delete'];
                return $subModule->has_access && Route::has($subModule->route_name);
            })->values();

            return $module;
        });

        // Get approval counts for the user
        $pendingApprovals = approval_transaction::pendingFor($user)->count();

        // Get recent activity for the user
        $recentActivity = $this->getRecentActivity($user);

        // Get quick stats based on user's role
        $stats = $this->getDashboardStats($user);

        return view('dashboard', compact(
            'modules',
            'authService',
            'pendingApprovals',
            'recentActivity',
            'stats'
        ));
    }

    public function mainDashboard()
    {
        $user = Auth::user();
        $authService = new AuthorizationService($user);

        // Get approval counts for the user
        $pendingApprovals = approval_transaction::pendingFor($user)->count();

        // Get recent activity for the user
        $recentActivity = $this->getRecentActivity($user);

        // Get quick stats based on user's role
        $stats = $this->getDashboardStats($user);

        return view('dashboard.index', compact(
            'authService',
            'pendingApprovals',
            'recentActivity',
            'stats'
        ));
    }

    protected function getRecentActivity($user)
    {
        $activities = collect();

        // RFDs created by user
        if ($this->hasAccess($user, Modules::AP, Modules::AP_RFD)) {
            $rfds = rfd_header::where('created_by', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($rfd) {
                    return [
                        'type' => 'RFD',
                        'reference' => 'RFD-' . str_pad($rfd->id, 6, '0', STR_PAD_LEFT),
                        'description' => $rfd->remarks ?? 'Request for Disbursement',
                        'status' => $rfd->statusLabel(),
                        'amount' => $rfd->total_due,
                        'created_at' => $rfd->created_at,
                        'url' => route('ap.rfd.edit', $rfd),
                    ];
                });
            $activities = $activities->merge($rfds);
        }

        // Cash Advances created by user
        if ($this->hasAccess($user, Modules::CM, Modules::CM_CA)) {
            $cas = CashAdvance::where('created_by', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($ca) {
                    return [
                        'type' => 'Cash Advance',
                        'reference' => 'CA-' . str_pad($ca->id, 6, '0', STR_PAD_LEFT),
                        'description' => $ca->purpose ?? 'Cash Advance Request',
                        'status' => $ca->statusLabel(),
                        'amount' => $ca->amount,
                        'created_at' => $ca->created_at,
                        'url' => route('cm.ca.edit', $ca),
                    ];
                });
            $activities = $activities->merge($cas);
        }

        // Journal Entries created by user
        if ($this->hasAccess($user, Modules::GL, Modules::GL_JOURNAL)) {
            $journals = gl_journal::where('created_by', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($journal) {
                    return [
                        'type' => 'Journal Entry',
                        'reference' => $journal->journal_no,
                        'description' => $journal->description ?? 'Journal Entry',
                        'status' => $journal->statusLabel(),
                        'amount' => $journal->total_debit,
                        'created_at' => $journal->created_at,
                        'url' => route('gl.journals.edit', $journal),
                    ];
                });
            $activities = $activities->merge($journals);
        }

        // Sort by created_at and take 10
        return $activities->sortByDesc('created_at')->take(10);
    }

    /**
     * Get dashboard stats based on user role
     */
    protected function getDashboardStats($user)
    {
        $stats = [];

        // Admin gets all stats
        if ($user->isAdmin()) {
            $stats = [
                'total_rfds' => rfd_header::count(),
                'total_cash_advances' => CashAdvance::count(),
                'total_journals' => gl_journal::count(),
                'pending_approvals' => approval_transaction::where('status', 'pending')->count(),
            ];
        } else {
            // Regular user gets their stats
            $stats = [
                'my_rfds' => rfd_header::where('created_by', $user->id)->count(),
                'my_cash_advances' => CashAdvance::where('created_by', $user->id)->count(),
                'pending_approvals' => approval_transaction::pendingFor($user)->count(),
                'my_journals' => gl_journal::where('created_by', $user->id)->count(),
            ];
        }

        return $stats;
    }

    /**
     * Check if user has access to a module
     */
    protected function hasAccess($user, string $moduleCode, ?string $subModuleCode = null): bool
    {
        $authService = new AuthorizationService($user);
        return $authService->canRead($moduleCode, $subModuleCode);
    }
}