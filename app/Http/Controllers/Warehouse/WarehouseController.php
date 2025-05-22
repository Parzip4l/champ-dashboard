<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

// Models
use App\Models\Warehouse\WarehouseItem;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseStock;
use App\Models\Warehouse\WarehouseStockMutation;

class WarehouseController extends Controller
{

    public function index(Request $request)
    {
        $locationId = $request->query('location');
        $category = $request->query('category');
        $search = $request->query('search');

        $items = WarehouseItem::with(['stocks' => function ($query) use ($locationId) {
            if ($locationId) {
                $query->where('warehouse_location_id', $locationId);
            }
            $query->with('location');
        }])
        ->when($category, fn($q) => $q->where('category', $category))
        ->when($search, fn($q) => $q->where('name', 'like', '%' . $search . '%'))
        ->paginate(10);

        if ($request->ajax()) {
            return view('general.warehouse.index', compact('items'))->renderSections()['locationsList'];
        }

        return view('general.warehouse.index', compact('items'));
    }

    public function create()
    {
        $locations = WarehouseLocation::all();
        return view('general.warehouse.create', compact('locations'));
    }

    public function store(Request $request)
    {
        // Validasi data input
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:255',
            'unit'         => 'required|string|max:50',
            'minimum_qty'  => 'required|numeric|min:0',
            'type'         => 'required|string|max:100',
        ]);

        $locationId = 1; // Lokasi default input stok awal
        $qty = $request->stokawal ?? 0;

        DB::beginTransaction();

        try {
            // Buat kode unik otomatis berdasarkan kategori
            $prefix = strtoupper($request->category);
            do {
                $random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                $code = $prefix . '-' . $random;
            } while (WarehouseItem::where('code', $code)->exists());

            // Simpan item baru
            $item = WarehouseItem::create([
                'name'         => $request->name,
                'type'         => $request->type,
                'category'     => $request->category,
                'unit'         => $request->unit,
                'minimum_qty'  => $request->minimum_qty,
                'code'         => $code,
            ]);

            // Simpan stok awal dan mutasi hanya jika qty > 0
            if ($qty > 0) {
                WarehouseStock::create([
                    'warehouse_item_id'       => $item->id,
                    'warehouse_location_id'   => $locationId,
                    'quantity'                => $qty,
                ]);

                WarehouseStockMutation::create([
                    'warehouse_item_id'       => $item->id,
                    'warehouse_location_id'   => $locationId,
                    'type'                    => 'in',
                    'quantity'                => $qty,
                    'note'                    => 'Stok awal saat input item',
                    'source'                  => 'system',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('warehouse.index')
                ->with('success', 'Item berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan item gudang: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors(['message' => 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.'])
                ->withInput();
        }
    }

    public function edit($id)
    {
        $item = WarehouseItem::findOrFail($id);
        $locations = WarehouseLocation::where('code','WH-001')->first();
        $stock = WarehouseStock::where('warehouse_item_id', $id)
                ->where('warehouse_location_id', $locations->id)
                ->first();
        $stokawal = $stock ? $stock->quantity : 0;

        return view('general.warehouse.edit', compact('item', 'locations', 'stokawal'));
    }

    public function show($id)
    {
        $item = WarehouseItem::findOrFail($id);
        $locations = WarehouseLocation::where('code', 'WH-001')->first();

        $stock = WarehouseStock::where('warehouse_item_id', $id)
            ->where('warehouse_location_id', $locations->id)
            ->first();

        $stokawal = $stock ? $stock->quantity : 0;
        $minimumStock = $item->minimum_qty ?? 0;

        // 5 mutasi terakhir
        $mutations = WarehouseStockMutation::where('warehouse_item_id', $id)
            ->where('warehouse_location_id', $locations->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Cek status stok
        $stockStatus = $stokawal < $minimumStock
            ? 'Stok di bawah minimum bulan ini!'
            : 'Stok aman bulan ini.';

        // Data grafik stok
        $chartMutations = WarehouseStockMutation::where('warehouse_item_id', $id)
            ->where('warehouse_location_id', $locations->id)
            ->whereMonth('created_at', now()->month)
            ->orderBy('created_at', 'asc')
            ->get();

        $stokAwal = WarehouseStockMutation::where('warehouse_item_id', $id)
            ->where('warehouse_location_id', $locations->id)
            ->where('created_at', '<', now()->startOfMonth())
            ->orderBy('created_at', 'desc')
            ->first()?->quantity_after ?? 0;
        
        // Inisialisasi
        $labels = [];
        $dataQty = [];
        $runningTotal = $stokAwal;
        
        // Loop mutasi
        foreach ($chartMutations as $mutation) {
            $labels[] = $mutation->created_at->format('d M');
        
            if ($mutation->type === 'in') {
                $runningTotal += $mutation->quantity;
            } elseif ($mutation->type === 'out') {
                $runningTotal -= $mutation->quantity;
            } elseif ($mutation->type === 'adjustment') {
                $runningTotal = $mutation->quantity; // adjustment langsung ubah total
            }
        
            $dataQty[] = $runningTotal;
        }

        $unit = $item->unit;

        return view('general.warehouse.singleitem', compact(
            'item', 'locations', 'stokawal', 'mutations', 'labels', 'dataQty', 'minimumStock', 'stockStatus','chartMutations','unit'
        ));
    }


    
    public function update(Request $request, $id)
    {
        $userName = auth()->user()->name ?? 'Unknown User';
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:255',
            'unit'         => 'required|string|max:50',
            'minimum_qty'  => 'required|numeric|min:0',
            'type'         => 'required|string|max:100',
            'stokawal'     => 'nullable|numeric|min:0',
        ]);

        $locations = WarehouseLocation::where('code','WH-001')->first();
        $qty = $request->stokawal ?? 0;

        DB::beginTransaction();

        try {
            $item = WarehouseItem::findOrFail($id);

            // Update data item
            $item->update([
                'name'         => $request->name,
                'type'         => $request->type,
                'category'     => $request->category,
                'unit'         => $request->unit,
                'minimum_qty'  => $request->minimum_qty,
                // kode biasanya tidak diubah saat update
            ]);

            // Update stok awal di lokasi default
            $stock = WarehouseStock::where('warehouse_item_id', $id)
            ->where('warehouse_location_id', $locations->id)
            ->first();

            if ($stock) {
                $qtyBefore = $stock->quantity;
                $diff = $qty - $qtyBefore;
                $type = 'adjustment';
                if ($diff != 0) {
                    $stock->quantity = $qty;
                    $stock->save();
            
                    WarehouseStockMutation::create([
                        'warehouse_item_id'       => $id,
                        'warehouse_location_id'   => $locations->id,
                        'type'                    => $type,
                        'quantity'                => abs($diff),
                        'quantity_before'         => $qtyBefore,
                        'quantity_after'          => $qty,
                        'note'                    => "Penyesuaian stok saat edit item oleh {$userName}",
                        'source'                  => 'system',
                    ]);
                }
            } else {
                if ($qty > 0) {
                    WarehouseStock::create([
                        'warehouse_item_id'       => $id,
                        'warehouse_location_id'   => $locations->id,
                        'quantity'                => $qty,
                    ]);
            
                    WarehouseStockMutation::create([
                        'warehouse_item_id'       => $id,
                        'warehouse_location_id'   => $locations->id,
                        'type'                    => 'in',
                        'quantity'                => $qty,
                        'quantity_before'         => 0,
                        'quantity_after'          => $qty,
                        'note'                    => "Stok dibuat saat edit item oleh {$userName}",
                        'source'                  => 'system',
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('warehouse.index')
                ->with('success', 'Item berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui item gudang: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors(['message' => 'Terjadi kesalahan saat memperbarui. Silakan coba lagi.'])
                ->withInput();
        }
    }

    public function download(Request $request)
    {
        
        $start = Carbon::parse($request->input('start_date'))->startOfDay();
        $end = Carbon::parse($request->input('end_date'))->endOfDay();
        $item = $request->input('item');
        $itemid = $request->input('item_id');

        $mutations = WarehouseStockMutation::whereBetween('created_at', [$start, $end])
                ->where('warehouse_item_id', $itemid)
                ->orderBy('created_at', 'desc')
                ->get();

        $pdf = Pdf::loadView('general.warehouse.reportitem', [
            'mutations' => $mutations,
            'start' => $start,
            'end' => $end,
            'item' => $item,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('report_mutasi_' . $item . '_' . $start . '_to_' . $end . '.pdf');
    }

}
