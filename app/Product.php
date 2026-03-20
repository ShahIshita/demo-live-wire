<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'stock_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public function getEffectivePriceAttribute()
    {
        $variant = $this->variants()->where('stock_quantity', '>', 0)->first();
        return $variant ? $variant->price : $this->price;
    }

    public function getTotalStockAttribute()
    {
        $base = $this->stock_quantity ?? 0;
        $variantStock = $this->variants()->sum('stock_quantity');
        return $base + $variantStock;
    }
}
