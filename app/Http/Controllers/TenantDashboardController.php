<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->currentTenant();
        $userTenants = $request->user()->tenants()->get();

        $metrics = [
            'plan' => ucfirst($tenant->plan),
            'projects_count' => $tenant->projects()->count(),
            'max_projects' => $tenant->plan === 'enterprise' ? 'Unlimited' : $tenant->max_projects,
            'members_count' => $tenant->users()->count(),
            'max_members' => $tenant->plan === 'enterprise' ? 'Unlimited' : $tenant->max_members,
            'role' => $tenant->userRole($request->user()) ?? 'member',
            'status' => $tenant->status,
        ];

        $projects = $tenant->projects()->latest()->take(5)->get();
        $members = $tenant->users()->take(5)->get();
        $audits = $tenant->auditLogs()->with('user')->latest()->take(5)->get();

        return view('dashboard', compact('tenant', 'userTenants', 'metrics', 'projects', 'members', 'audits'));
    }
}
