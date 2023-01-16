<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $appends = ['date'];


    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function productVariantPrices()
    {
        return $this->hasMany(ProductVariantPrice::class)->with('variantOne');
    }

    public function getDateAttribute($key)
    {
        return $this->created_at->format('d M Y');
    }
}
