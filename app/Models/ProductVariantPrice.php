<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{

    protected $appends = ['variantName'];

    public  function variantOne() {
        return $this->belongsTo(ProductVariant::class,'product_variant_one','id');
    }
    public  function variantTwo() {
        return $this->belongsTo(ProductVariant::class,'product_variant_two','id');
    }
    public  function variantThree() {
        return $this->belongsTo(ProductVariant::class,'product_variant_three','id');
    }

    public  function  getVariantNameAttribute()
    {
        $data = '';
        if(!blank($this->variantOne)){
            $data = $this->variantOne->variant;
        }
        if (!blank($this->variantTwo)){
            $data .= ' / '.$this->variantTwo->variant;
        }
        if (!blank($this->variantThree)){
            $data .= '/ '.$this->variantThree->variant;
        }

        return $data;
    }
}
