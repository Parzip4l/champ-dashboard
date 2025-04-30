<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Production\ProductionMaterialTank;
use App\Models\Production\ProductionBatch;

class ProductionMaterial extends Model
{
    use HasFactory;
    protected $table = 'production_materials';
    protected $fillable = [
        'production_batch_id', 'step', 'kategori', 'tipe', 'jenis', 'qty', 'keterangan'
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }
}
