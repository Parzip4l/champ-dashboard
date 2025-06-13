<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    protected $fillable = ['name', 'category', 'unit', 'type', 'code', 'minimum_qty'];

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class, 'warehouse_item_id');
    }
}
