<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'courier_id',
        'external_courier_name',
        'external_courier_phone',
        'status',
        'type',
        'scheduled_at',
        'notes',
        'proof_image',
        'current_lat',
        'current_lng',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }
}
