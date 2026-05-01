<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'service_id',
        'item_name',
        'qty',
        'estimated_weight_option',
        'actual_weight',
        'price',
        'use_discount',
        'discount_amount',
    ];

    protected $casts = [
        'qty'             => 'decimal:2',
        'actual_weight'   => 'decimal:2',
        'price'           => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'use_discount'    => 'boolean',
    ];

    /**
     * Label Indonesia untuk opsi estimasi berat.
     */
    public static array $weightLabels = [
        'kurang_dari_3'  => 'Kurang dari 3 kg',
        '3_sampai_5'     => '3 sampai 5 kg',
        '5_sampai_10'    => '5 sampai 10 kg',
        'lebih_dari_10'  => 'Lebih dari 10 kg',
    ];

    /**
     * Range estimasi (min/max kg) untuk kalkulasi biaya estimasi.
     */
    public static array $weightRange = [
        'kurang_dari_3' => ['min' => 1,  'max' => 3],
        '3_sampai_5'    => ['min' => 3,  'max' => 5],
        '5_sampai_10'   => ['min' => 5,  'max' => 10],
        'lebih_dari_10' => ['min' => 10, 'max' => 15],
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function extras()
    {
        return $this->hasMany(OrderItemExtra::class);
    }

    /**
     * Label estimasi berat dalam Bahasa Indonesia.
     */
    public function getEstimatedWeightLabelAttribute(): ?string
    {
        if (!$this->estimated_weight_option) return null;
        return static::$weightLabels[$this->estimated_weight_option] ?? null;
    }

    /**
     * Apakah sudah ditimbang (berat aktual sudah diisi).
     */
    public function getIsWeighedAttribute(): bool
    {
        return $this->actual_weight !== null && $this->actual_weight > 0;
    }
}
