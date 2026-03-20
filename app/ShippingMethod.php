<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name', 'description', 'charge', 'min_order_amount',
        'estimated_days', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_method_id');
    }
}
