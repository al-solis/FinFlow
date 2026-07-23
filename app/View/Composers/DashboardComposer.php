<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\module;
use App\Models\sub_module;

class DashboardComposer
{
    public function compose(View $view)
    {
        $modules = module::with('accessRights')
            ->orderBy('sequence')
            ->get();

        $subModules = sub_module::all();

        $view->with([
            'modules' => $modules,
            'subModules' => $subModules,
        ]);
    }
}