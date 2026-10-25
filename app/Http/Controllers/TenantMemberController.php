<?php

namespace App\Http\Controllers;

use App\Models\TenantAuditLog;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantMemberController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->currentTenant();
        $members = $tenant->users()->get();
        $invitations = $tenant->invitations()->where('status', 'pending')->get();
        $role = $tenant->userRole($request->user());

        return view('members.index', compact('tenant', 'members', 'invitations', 'role'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();

        if ($tenant->hasReachedMemberLimit()) {
            return back()->with('error', "Member limit reached for the {$tenant->plan} plan ({$tenant->max_members} max).");
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,member',
        ]);

        // If user already exists in system, add directly
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            if ($tenant->users()->where('users.id', $existingUser->id)->exists()) {
                return back()->with('error', 'User is already a member of this workspace.');
            }
            $tenant->users()->attach($existingUser->id, ['role' => $validated['role']]);
            $msg = "Added {$existingUser->name} as {$validated['role']}.";
        } else {
            TenantInvitation::create([
                'tenant_id' => $tenant->id,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'token' => Str::random(40),
                'status' => 'pending',
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(7),
            ]);
            $msg = "Invitation created for {$validated['email']}.";
        }

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'INVITE_MEMBER',
            'description' => "Invited {$validated['email']} as {$validated['role']}",
        ]);

        return redirect()->route('members.index')->with('success', $msg);
    }

    public function remove(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->currentTenant();
        $callerRole = $tenant->userRole($request->user());

        if (!in_array($callerRole, ['owner', 'admin'])) {
            abort(403, 'Only owners and admins can remove members.');
        }

        if ($user->id === $tenant->created_by) {
            return back()->with('error', 'Cannot remove the organization owner.');
        }

        $tenant->users()->detach($user->id);

        TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'REMOVE_MEMBER',
            'description' => "Removed {$user->email} from workspace",
        ]);

        return redirect()->route('members.index')->with('success', "Removed {$user->name} from workspace.");
    }
}
