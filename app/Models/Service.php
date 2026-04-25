<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'category',
        'category_id',
        'price',
        'estimate',
        'status',
        'description',
        'image',
        'features',
        'unit',
        'tag',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
