<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(TenantProject::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TenantAuditLog::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasReachedProjectLimit(): bool
    {
        if ($this->plan === 'enterprise') {
            return false;
        }
        $limit = $this->max_projects ?: 3;
        return $this->projects()->count() >= $limit;
    }

    public function hasReachedMemberLimit(): bool
    {
        if ($this->plan === 'enterprise') {
            return false;
        }
        $limit = $this->max_members ?: 5;
        return $this->users()->count() >= $limit;
    }

    public function userRole(User $user): ?string
    {
        $member = $this->users()->where('user_id', $user->id)->first();
        return $member?->pivot?->role;
    }
}
