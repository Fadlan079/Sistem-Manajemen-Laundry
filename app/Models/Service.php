<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'category',
        'price',
        'estimate',
        'status',
        'description',
        'icon',
        'features',
        'unit',
        'tag',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
