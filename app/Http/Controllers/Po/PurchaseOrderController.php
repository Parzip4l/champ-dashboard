<?php

namespace App\Http\Controllers\Po;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

// Model
use App\Models\Po\PurchaseOrder;
use App\Models\Po\PurchaseOrderItem;
use App\Models\General\Distributor;
use App\Exports\PurchaseOrdersExport;
use App\Models\Po\PurchaseReturn;
use App\Models\Po\PurchaseReturnItem;

// warehouse
use App\Models\Warehouse\WarehouseItem;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseStock;
use App\Models\Warehouse\WarehouseStockMutation;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = PurchaseOrder::with(['items.warehouseItem', 'distributor']);

        if ($search) {
            $query->where('po_number', 'like', "%{$search}%")
                ->orWhereHas('distributor', fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('items.warehouseItem', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $orders = $query->paginate(10);

        // Tidak perlu response JSON, kembalikan HTML biasa agar bisa diparsing di jQuery
        return view('po.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Distributor::all();
        $items = WarehouseItem::all();
        return view('po.create', compact('suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:distributor,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:order_date',
            'terms_of_payment' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.warehouse_item_id' => 'required|exists:warehouse_items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $po = new PurchaseOrder();
            $po->distributor_id = $request->supplier_id;
            $po->po_date = $request->order_date;
            $po->due_date = $request->due_date;
            $po->top = $request->terms_of_payment;
            $po->discount = $request->discount ?? 0;
            $po->tax = $request->tax ?? 0;
            $po->created_by = auth()->id();

            // Hitung subtotal dari items
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            $po->subtotal = $subtotal;
            $po->total = $subtotal - $po->discount + $po->tax;

            $po->save();

            // Simpan item
            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'warehouse_item_id' => $item['warehouse_item_id'],
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase_orders.index')
                ->with('success', 'Purchase Order berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.warehouseItem', 'distributor');

        $prev = PurchaseOrder::where('id', '<', $purchaseOrder->id)->orderBy('id', 'desc')->first();
        $next = PurchaseOrder::where('id', '>', $purchaseOrder->id)->orderBy('id')->first();

        return view('po.show', compact('purchaseOrder', 'prev', 'next'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items');
        $suppliers = Distributor::all();
        $items = WarehouseItem::all();
        return view('po.edit', compact('purchaseOrder', 'suppliers', 'items'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'supplier_id' => 'required|exists:distributor,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:order_date',
            'terms_of_payment' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.warehouse_item_id' => 'required|exists:warehouse_items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $purchaseOrder->distributor_id = $request->supplier_id;
            $purchaseOrder->po_date = $request->order_date;
            $purchaseOrder->due_date = $request->due_date;
            $purchaseOrder->top = $request->terms_of_payment;
            $purchaseOrder->discount = $request->discount ?? 0;
            $purchaseOrder->tax = $request->tax ?? 0;

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            $purchaseOrder->subtotal = $subtotal;
            $purchaseOrder->total = $subtotal - $purchaseOrder->discount + $purchaseOrder->tax;
            $purchaseOrder->save();

            $existingIds = $purchaseOrder->items()->pluck('id')->toArray();
            $submittedIds = collect($request->items)->pluck('id')->filter()->toArray();

            // Hapus item yang tidak ada di form
            $toDelete = array_diff($existingIds, $submittedIds);
            PurchaseOrderItem::destroy($toDelete);

            // Update atau create item
            foreach ($request->items as $item) {
                PurchaseOrderItem::updateOrCreate(
                    ['id' => $item['id'] ?? 0],
                    [
                        'purchase_order_id' => $purchaseOrder->id,
                        'warehouse_item_id' => $item['warehouse_item_id'],
                        'quantity' => $item['quantity'],
                        'uom' => $item['uom'],
                        'price' => $item['price'],
                        'subtotal' => $item['quantity'] * $item['price'],
                        'notes' => $item['notes'] ?? null,
                    ]
                );
            }
            DB::commit();

            return redirect()->route('purchase_orders.index')
                ->with('success', 'Purchase Order berhasil diupdate.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengupdate data: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();
        return redirect()->route('purchase_orders.index')->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function received(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $receivedItems = $request->input('received', []);
            
            // Ambil lokasi warehouse berdasarkan kode
            $lokasi = WarehouseLocation::where('code', 'WH-001')->first();

            // Validasi lokasi
            if (!$lokasi) {
                return back()->withErrors("Lokasi warehouse dengan kode WH-001 tidak ditemukan.");
            }

            $locationId = $lokasi->id;

            // Validasi qty received per item
            foreach ($purchaseOrder->items as $item) {
                if (!isset($receivedItems[$item->id])) {
                    return back()->withErrors("Jumlah diterima untuk item {$item->id} tidak ditemukan.");
                }
                $qty = (int) $receivedItems[$item->id];
                if ($qty < 0 || $qty > $item->quantity) {
                    return back()->withErrors("Jumlah diterima untuk item {$item->id} harus antara 0 dan {$item->quantity}.");
                }
            }

            // Proses transaksi
            DB::transaction(function () use ($purchaseOrder, $receivedItems, $locationId) {
                foreach ($purchaseOrder->items as $item) {
                    $qtyReceived = (int) ($receivedItems[$item->id] ?? 0);
                    if ($qtyReceived > 0) {
                        // Update received quantity
                        $item->received_quantity = ($item->received_quantity ?? 0) + $qtyReceived;
                        $item->save();

                        // Update atau buat stok warehouse
                        $stock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_item_id' => $item->warehouse_item_id,
                                'warehouse_location_id' => $locationId,
                            ],
                            [
                                'quantity' => 0,
                            ]
                        );

                        $quantityBefore = $stock->quantity;
                        $quantityAfter = $quantityBefore + $qtyReceived;

                        $stock->quantity = $quantityAfter;
                        $stock->save();

                        // Mutasi stok
                        WarehouseStockMutation::create([
                            'warehouse_item_id'       => $item->warehouse_item_id,
                            'warehouse_location_id'   => $locationId,
                            'type'                    => 'in',
                            'quantity'                => $qtyReceived,
                            'quantity_before'         => $quantityBefore,
                            'quantity_after'          => $quantityAfter,
                            'note'                    => "Receive dari PO #{$purchaseOrder->id}",
                            'source'                  => 'purchase_order',
                        ]);
                    }
                }

                // Cek status PO
                $allReceived = $purchaseOrder->items->every(fn($i) => ($i->received_quantity ?? 0) >= $i->quantity);
                $purchaseOrder->status = $allReceived ? 'received' : 'partial';
                $purchaseOrder->save();
            });

            return redirect()->route('purchase_orders.show', $purchaseOrder->id)
                ->with('success', 'Berhasil menerima barang, stok warehouse telah diperbarui.');

        } catch (\Throwable $e) {
            // Tangani error
            \Log::error('Gagal menerima barang: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->withErrors(['message' => 'Terjadi kesalahan saat menerima barang: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // Return
    public function returnItem(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with(['items'])->findOrFail($id);

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

            $returnMap = collect();

            foreach ($data['items'] as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'warehouse_item_id' => $item['warehouse_item_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'] ?? null,
                ]);

                // Update stok
                $stock = WarehouseStock::where('warehouse_item_id', $item['warehouse_item_id'])->first();
                $qtyBefore = $stock->quantity ?? 0;
                $qtyAfter = $qtyBefore - $item['quantity'];
                $stock->quantity = $qtyAfter;
                $stock->save();

                WarehouseStockMutation::create([
                    'warehouse_item_id' => $item['warehouse_item_id'],
                    'warehouse_location_id' => $stock->warehouse_location_id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'note' => 'Retur barang ke supplier dari PO #' . $purchaseOrder->id,
                    'source' => 'purchase_return',
                ]);

                // Simpan akumulasi retur per item
                $returnMap->put($item['purchase_order_item_id'], $returnMap->get($item['purchase_order_item_id'], 0) + $item['quantity']);
            }

            // Update status item (misalnya menandai jumlah yang diretur)
            foreach ($returnMap as $itemId => $returnQty) {
                $orderItem = PurchaseOrderItem::find($itemId);
                $orderItem->returned_quantity = ($orderItem->returned_quantity ?? 0) + $returnQty;
                $orderItem->received_quantity = max(0, ($orderItem->received_quantity ?? 0) - $returnQty);
                $orderItem->save();
            }

            $purchaseOrder->load('items');

            // Cek apakah semua item sudah diretur penuh
            $allReturned = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
            ->get()
            ->every(function ($item) {
                return ($item->returned_quantity ?? 0) >= ($item->received_quantity ?? 0);
            });

            if ($allReturned) {
                $purchaseOrder->status = 'returned';
                $purchaseOrder->save();
            }
        });

        return redirect()->back()->with('success', 'Retur pembelian berhasil disimpan.');
    }



    public function export()
    {
        return Excel::download(new PurchaseOrdersExport, 'purchase_orders.xlsx');
    }

    public function printPdf(PurchaseOrder $purchase_order)
    {
        // Load relasi yang dibutuhkan
        $purchase_order->load(['items.warehouseItem', 'distributor']);

        // Buat view PDF tanpa log aktivitas
        $pdf = PDF::loadView('po.print', compact('purchase_order'));

        // Download sebagai file PDF
        return $pdf->stream('purchase_order_' . $purchase_order->po_number . '.pdf');
    }
}
