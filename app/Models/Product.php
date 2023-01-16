<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $appends = ['date','ProductVariantList'];


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
        return $this->hasMany(ProductVariantPrice::class)->with('variantOne','variantTwo','variantThree');
    }

    public function getDateAttribute($key)
    {
        return $this->created_at->format('d M Y');
    }

    public function getProductVariantListAttribute($key)
    {
        $data = [];
        if(!blank($this->productVariants)){
            foreach ($this->productVariants as $productVariant) {
                $data[$productVariant->variant_id][] = $productVariant->variant;
            }
        }
        return $data;
    }
}
