<?php

namespace App\Http\Controllers;

use App\Models\TenantAuditLog;
use App\Models\TenantProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantProjectController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->currentTenant();
        $projects = $tenant->projects()->with('creator')->latest()->paginate(15);
        return view('projects.index', compact('tenant', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();

        if ($tenant->isSuspended()) {
            return back()->with('error', 'Cannot create project: organization is suspended.');
        }

        if ($tenant->hasReachedProjectLimit()) {
            return back()->with('error', "Project limit reached for the {$tenant->plan} plan ({$tenant->max_projects} max). Upgrade plan to add more.");
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = $tenant->projects()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
            'status' => 'active',
        ]);

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'CREATE_PROJECT',
            'description' => "Created project {$project->title}",
        ]);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function destroy(Request $request, TenantProject $project): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();

        // Enforce tenant boundary
        if ($project->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to project.');
        }

        $project->delete();

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'DELETE_PROJECT',
            'description' => "Deleted project {$project->title}",
        ]);

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
