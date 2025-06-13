<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class WarehouseStockMutation extends Model
{
    protected $table = 'warehouse_stocks_mutations';
    protected $fillable = [
        'warehouse_location_id',
        'warehouse_item_id',
        'type',
        'quantity',
        'note',
        'source',
        'quantity_before',
        'quantity_after'
    ];

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function item()
    {
        return $this->belongsTo(WarehouseItem::class);
    }
}
