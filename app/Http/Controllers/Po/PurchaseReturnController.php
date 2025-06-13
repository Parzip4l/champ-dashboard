<?php

namespace App\Http\Controllers\Po;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Model

use App\Models\Po\PurchaseOrder;
use App\Models\Po\PurchaseReturn;
use App\Models\Po\PurchaseReturnItem;
use App\Models\Warehouse\WarehouseStock;
use App\Models\Warehouse\WarehouseStockMutation;

class PurchaseReturnController extends Controller
{
    public function create(PurchaseOrder $purchaseOrder)
    {
        return view('purchase_returns.create', compact('purchaseOrder'));
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'return_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.warehouse_item_id' => 'required|exists:warehouse_items,id',
            'items.*.reason' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $return = PurchaseReturn::create([
                'purchase_order_id' => $purchaseOrder->id,
                'return_date' => $data['return_date'],
                'status' => 'returned',
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'warehouse_item_id' => $item['warehouse_item_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ]);

                // Kurangi stok
                $stock = WarehouseStock::where('warehouse_item_id', $item['warehouse_item_id'])->first();
                $qtyBefore = $stock->quantity ?? 0;
                $qtyAfter = $qtyBefore - $item['quantity'];
                $stock->quantity = $qtyAfter;
                $stock->save();

                WarehouseStockMutation::create([
                    'warehouse_item_id' => $item['warehouse_item_id'],
                    'warehouse_location_id' => $stock->warehouse_location_id,
                    'type' => 'return',
                    'quantity' => $item['quantity'],
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'note' => 'Retur barang ke supplier dari PO #' . $purchaseOrder->id,
                    'source' => 'purchase_return',
                ]);
            }
        });

        return redirect()->route('purchase_orders.show', $purchaseOrder->id)
            ->with('success', 'Retur barang berhasil disimpan dan stok dikurangi.');
    }
}
