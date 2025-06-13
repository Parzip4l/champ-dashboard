<?php

namespace App\Models\Po;

use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse\WarehouseItem;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'warehouse_item_id', 'quantity','uom','price', 'subtotal', 'notes'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouseItem()
    {
        return $this->belongsTo(WarehouseItem::class);
    }
}
