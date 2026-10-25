<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dashboard_auto_creates_primary_workspace(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee("Alice's Workspace");

        $this->assertDatabaseHas('tenants', [
            'name' => "Alice's Workspace",
            'created_by' => $user->id,
            'plan' => 'free',
        ]);
    }

    public function test_user_cannot_switch_to_unauthorized_tenant(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $tenantA = $userA->currentTenant();
        $tenantB = $userB->currentTenant();

        // Bob tries to switch to Alice's tenant
        $response = $this->actingAs($userB)->post("/tenants/{$tenantA->id}/switch");
        $response->assertStatus(403);
    }

    public function test_cross_tenant_project_isolation(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $tenantA = $userA->currentTenant();
        $tenantB = $userB->currentTenant();

        $projectA = $tenantA->projects()->create([
            'title' => 'Secret Project Alpha',
            'created_by' => $userA->id,
        ]);

        // User B tries to delete User A's project
        $response = $this->actingAs($userB)->delete("/projects/{$projectA->id}");
        $response->assertStatus(403);

        $this->assertDatabaseHas('tenant_projects', [
            'id' => $projectA->id,
            'title' => 'Secret Project Alpha',
        ]);
    }

    public function test_plan_project_limit_enforcement(): void
    {
        $user = User::factory()->create();
        $tenant = $user->currentTenant();
        $tenant->update(['max_projects' => 2]);

        // Create 2 projects
        $tenant->projects()->create(['title' => 'Project 1', 'created_by' => $user->id]);
        $tenant->projects()->create(['title' => 'Project 2', 'created_by' => $user->id]);

        // Attempt 3rd project
        $response = $this->actingAs($user)->post('/projects', [
            'title' => 'Project 3 Exceeding Limit',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('tenant_projects', [
            'title' => 'Project 3 Exceeding Limit',
        ]);
    }

    public function test_plan_upgrade_and_status_suspension(): void
    {
        $user = User::factory()->create();
        $tenant = $user->currentTenant();

        // Upgrade to Pro
        $response = $this->actingAs($user)->post('/plans/upgrade', [
            'plan' => 'pro',
        ]);

        $response->assertRedirect('/plans');
        $tenant->refresh();
        $this->assertEquals('pro', $tenant->plan);
        $this->assertEquals(20, $tenant->max_projects);

        // Suspend workspace
        $suspendResponse = $this->actingAs($user)->post('/plans/status');
        $suspendResponse->assertSessionHas('success');
        $tenant->refresh();
        $this->assertTrue($tenant->isSuspended());

        // Suspended workspace rejects new projects
        $projectAttempt = $this->actingAs($user)->post('/projects', [
            'title' => 'Blocked in Suspended Workspace',
        ]);
        $projectAttempt->assertSessionHas('error');
    }

    public function test_member_invitation_workflow(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create(['email' => 'colleague@enterprise.com']);

        $response = $this->actingAs($user)->post('/members/invite', [
            'email' => 'colleague@enterprise.com',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/members');
        $tenant = $user->currentTenant();
        $this->assertTrue($tenant->users()->where('users.id', $colleague->id)->exists());
    }
}
