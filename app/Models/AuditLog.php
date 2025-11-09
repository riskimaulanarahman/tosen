<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Disable automatic timestamp management; table only stores created_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'risk_level',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include high-risk logs.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope a query to only include logs from last N days.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include logs for specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for specific resource.
     */
    public function scopeForResource($query, string $resourceType, ?int $resourceId = null)
    {
        $query->where('resource_type', $resourceType);
        
        if ($resourceId) {
            $query->where('resource_id', $resourceId);
        }
        
        return $query;
    }

    /**
     * Scope a query to only include logs from specific IP address.
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Get formatted description with context.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return "{$this->action} on {$this->resource_type} #{$this->resource_id}";
    }

    /**
     * Get risk level color for UI display.
     */
    public function getRiskLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }
}
