<?php

namespace App\Http\Controllers;

use App\Models\TenantAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPlanController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->currentTenant();
        $plans = [
            'free' => ['name' => 'Free', 'price' => 0, 'max_members' => 5, 'max_projects' => 3, 'storage_mb' => 100],
            'pro' => ['name' => 'Pro', 'price' => 29, 'max_members' => 25, 'max_projects' => 20, 'storage_mb' => 5000],
            'enterprise' => ['name' => 'Enterprise', 'price' => 99, 'max_members' => 'Unlimited', 'max_projects' => 'Unlimited', 'storage_mb' => 50000],
        ];

        return view('plans.index', compact('tenant', 'plans'));
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();
        $role = $tenant->userRole($request->user());

        if ($role !== 'owner') {
            abort(403, 'Only the organization owner can change subscription plans.');
        }

        $validated = $request->validate([
            'plan' => 'required|in:free,pro,enterprise',
        ]);

        $limits = [
            'free' => ['max_members' => 5, 'max_projects' => 3, 'storage_limit_mb' => 100],
            'pro' => ['max_members' => 25, 'max_projects' => 20, 'storage_limit_mb' => 5000],
            'enterprise' => ['max_members' => 999999, 'max_projects' => 999999, 'storage_limit_mb' => 50000],
        ];

        $tenant->update(array_merge(['plan' => $validated['plan']], $limits[$validated['plan']]));

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'UPGRADE_PLAN',
            'description' => "Changed subscription plan to " . ucfirst($validated['plan']),
        ]);

        return redirect()->route('plans.index')->with('success', "Plan updated to " . ucfirst($validated['plan']) . ".");
    }

    public function toggleStatus(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();
        $role = $tenant->userRole($request->user());

        if ($role !== 'owner') {
            abort(403, 'Only the organization owner can change status.');
        }

        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'TOGGLE_STATUS',
            'description' => "Changed organization status to {$newStatus}",
        ]);

        return back()->with('success', "Organization is now {$newStatus}.");
    }
}
