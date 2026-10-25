<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentTenant(): Tenant
    {
        $tenantId = session('current_tenant_id');
        if ($tenantId && $tenant = $this->tenants()->find($tenantId)) {
            return $tenant;
        }

        $tenant = $this->tenants()->first();
        if ($tenant) {
            session(['current_tenant_id' => $tenant->id]);
            return $tenant;
        }

        // Auto create primary organization for user
        $slug = \Illuminate\Support\Str::slug($this->name . '-workspace') . '-' . substr(uniqid(), -4);
        $newTenant = Tenant::create([
            'name' => $this->name . "'s Workspace",
            'slug' => $slug,
            'plan' => 'free',
            'max_members' => 5,
            'max_projects' => 3,
            'created_by' => $this->id,
        ]);

        $this->tenants()->attach($newTenant->id, ['role' => 'owner']);
        session(['current_tenant_id' => $newTenant->id]);

        return $newTenant;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }



    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
}
