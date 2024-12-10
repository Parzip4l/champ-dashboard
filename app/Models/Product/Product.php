<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'categories', 'weight', 'size', 'tag_number', 'stock', 'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
