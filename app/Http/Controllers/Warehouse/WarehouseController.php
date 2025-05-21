<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $locationId = 7; // Lokasi default input stok awal
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


}
