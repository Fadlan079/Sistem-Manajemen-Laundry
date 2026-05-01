<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'name',
        'type',
        'service_id',
        'day_of_week',
        'percentage',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'percentage'  => 'decimal:2',
        'is_active'   => 'boolean',
        'valid_from'  => 'date',
        'valid_until' => 'date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if this discount is valid today.
     */
    public function isValidToday(): bool
    {
        if (!$this->is_active) return false;

        if ($this->type === 'harian') {
            return now()->format('l') === $this->day_of_week;
        }

        if ($this->type === 'promo') {
            $today = now()->toDateString();
            $from  = $this->valid_from?->toDateString();
            $until = $this->valid_until?->toDateString();
            return (!$from || $today >= $from) && (!$until || $today <= $until);
        }

        return true; // 'member' etc — always valid if active
    }
}
