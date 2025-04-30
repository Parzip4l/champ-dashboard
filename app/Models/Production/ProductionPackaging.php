<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Production\ProductionBatch;

class ProductionPackaging extends Model
{
    use HasFactory;
    protected $table = 'production_packaging';

    protected $fillable = ['production_batch_id', 'packaging', 'size', 'quantity'];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class); // Relasi balik ke ProductionBatch
    }
}
