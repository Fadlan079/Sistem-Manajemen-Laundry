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
        'discount_day',
        'discount_percentage',
    ];

    protected $casts = [
        'features' => 'array',
        'discount_percentage' => 'decimal:2',
    ];

    protected $appends = ['is_discount_today', 'discounted_price'];

    public function getIsDiscountTodayAttribute()
    {
        if (!$this->discount_day || $this->discount_percentage <= 0) {
            return false;
        }

        return now()->format('l') === $this->discount_day;
    }

    public function getDiscountedPriceAttribute()
    {
        if (!$this->getIsDiscountTodayAttribute()) {
            return $this->price;
        }

        return $this->price * (1 - ($this->discount_percentage / 100));
    }

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
