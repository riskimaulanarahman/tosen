<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncFlowTrial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'employees',
        'industry',
        'ip_address',
        'user_agent',
        'status',
        'notes'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Follow-up',
            'contacted' => 'Sudah Dihubungi',
            'converted' => 'Konversi Berhasil',
            'rejected' => 'Ditolak'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get the status color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'contacted' => 'blue',
            'converted' => 'green',
            'rejected' => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for contacted requests
     */
    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope for converted requests
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
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
}
