<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Production\ProductionMaterial;
use App\Models\Production\ProductionPackaging;

class ProductionBatch extends Model
{
    use HasFactory;
    protected $fillable = [
        'batch_code', 'produk', 'tangki_masak', 'status', 'hasil_status',
        'tangki_olah', 'bahan_bakar_masak', 'qty_bahan_bakar_masak',
        'bahan_bakar_olah', 'qty_bahan_bakar_olah'
    ];

    public function materials()
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function packagingSizes()
    {
        return $this->hasMany(PackagingSize::class);
    }
}
