<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncFlowDemo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'employees',
        'preferred_date',
        'preferred_time',
        'notes',
        'ip_address',
        'user_agent',
        'status',
        'assigned_to'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'preferred_date' => 'datetime'
    ];

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Konfirmasi',
            'scheduled' => 'Demo Terjadwal',
            'completed' => 'Demo Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'scheduled' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get assigned user
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for scheduled requests
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope by preferred date range
     */
    public function scopePreferredDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('preferred_date', [$startDate, $endDate]);
    }

    /**
     * Scope by company
     */
    public function scopeByCompany($query, $company)
    {
        return $query->where('company', 'like', '%' . $company . '%');
    }

    /**
     * Scope by email
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', '%' . $email . '%');
    }

    /**
     * Scope by assigned user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for upcoming demos
     */
    public function scopeUpcoming($query)
    {
        return $query->where('preferred_date', '>=', now())
                    ->whereIn('status', ['pending', 'scheduled']);
    }

    /**
     * Scope for past demos
     */
    public function scopePast($query)
    {
        return $query->where('preferred_date', '<', now());
    }
}
