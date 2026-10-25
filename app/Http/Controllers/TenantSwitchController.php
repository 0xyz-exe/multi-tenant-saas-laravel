<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantSwitchController extends Controller
{
    public function switch(Request $request, Tenant $tenant): RedirectResponse
    {
        // Enforce membership boundary
        if (!$request->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
            abort(403, 'Unauthorized access to organization.');
        }

        session(['current_tenant_id' => $tenant->id]);

        return redirect()->route('dashboard')->with('success', "Switched workspace to {$tenant->name}.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $slug = Str::slug($validated['name']) . '-' . substr(uniqid(), -4);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'plan' => 'free',
            'created_by' => $request->user()->id,
        ]);

        $request->user()->tenants()->attach($tenant->id, ['role' => 'owner']);
        session(['current_tenant_id' => $tenant->id]);

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'CREATE_TENANT',
            'description' => "Created organization {$tenant->name}",
        ]);

        return redirect()->route('dashboard')->with('success', "Organization {$tenant->name} created.");
    }
}
