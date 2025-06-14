<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
Use Carbon\Carbon;

// Model
use App\Models\Production\ProductionMaterialTank;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionMaterial;
use App\Models\Production\ProductionPackaging;

// Warehouse
use App\Models\Warehouse\WarehouseItem;
use App\Models\Warehouse\WarehouseLocation;
use App\Models\Warehouse\WarehouseStock;
use App\Models\Warehouse\WarehouseStockMutation;


class ProductionBatchController extends Controller
{
    public function index()
    {
        $batches = ProductionBatch::orderBy('created_at', 'desc')
                ->paginate(10); 
                
        return view('general.produksi.index', compact('batches'));
    }

    // Form tambah produksi baru
    public function create()
    {
        return view('general.produksi.create');
    }

    // Simpan produksi baru
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $batch = ProductionBatch::create([
                'batch_code' => $request->batch_code,
                'produk' => $request->produk,
                'tanggal' => $request->tanggal,
                'tangki_masak' => $request->tangki_masak,
                'status' => $request->status,
                'hasil_status' => $request->hasil_status,
                'tangki_olah' => $request->tangki_olah,
                'bahan_bakar_masak' => $request->bahan_bakar,
                'qty_bahan_bakar_masak' => $request->bahan_bakar_masak,
                'bahan_bakar_olah' => $request->bahan_bakar,
                'qty_bahan_bakar_olah' => $request->bahan_bakar_masak,
            ]);

            // Simpan bahan baku dari Step 1 dan Step 2
            foreach (['step1', 'step2'] as $step) {
                foreach (['oli', 'lemak', 'kapur', 'pewarna', 'additif', 'bs'] as $kategori) {
                    $qtys = $request->input("{$kategori}_qty_{$step}", []);
                    foreach ($qtys as $i => $qty) {
                        if ($qty) {
                            ProductionMaterial::create([
                                'production_batch_id' => $batch->id,
                                'step' => $step,
                                'kategori' => $kategori,
                                'tipe' => $request->input("{$kategori}_tipe_{$step}")[$i] ?? null,
                                'jenis' => $request->input("{$kategori}_jenis_{$step}")[$i] ?? null,
                                'qty' => $qty,
                                'keterangan' => $request->input("{$kategori}_ket_{$step}")[$i] ?? null,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Batch berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $batch = ProductionBatch::with('materials')->findOrFail($id);

        // Group bahan baku berdasarkan step dan kategori
        $groupedMaterials = $batch->materials->groupBy(function($item) {
            return $item->step;
        })->map(function ($items) {
            return $items->groupBy('kategori');
        });

        return view('general.produksi.edit', compact('batch', 'groupedMaterials'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Update Production Batch
            $batch = ProductionBatch::findOrFail($id);
            $batch->update([
                'batch_code' => $request->batch_code,
                'produk' => $request->produk,
                'tangki_masak' => $request->tangki_masak,
                'status' => $request->status,
                'tanggal' => $request->tanggal,
                'hasil_status' => $request->hasil_status,
                'tangki_olah' => $request->tangki_olah,
                'bahan_bakar_masak' => $request->bahan_bakar,
                'qty_bahan_bakar_masak' => $request->bahan_bakar_masak,
                'bahan_bakar_olah' => $request->bahan_bakar,
                'qty_bahan_bakar_olah' => $request->bahan_bakar_masak,
            ]);

            // Update atau Hapus bahan baku yang ada
            foreach (['step1', 'step2'] as $step) {
                foreach (['oli', 'lemak', 'kapur', 'pewarna', 'additif', 'bs'] as $kategori) {
                    $qtys = $request->input("{$kategori}_qty_{$step}", []);
                    foreach ($qtys as $i => $qty) {
                        if ($qty) {
                            $material = ProductionMaterial::where('production_batch_id', $batch->id)
                                                        ->where('step', $step)
                                                        ->where('kategori', $kategori)
                                                        ->skip($i)
                                                        ->first();
                            if ($material) {
                                $material->update([
                                    'tipe' => $request->input("{$kategori}_tipe_{$step}")[$i] ?? null,
                                    'jenis' => $request->input("{$kategori}_jenis_{$step}")[$i] ?? null,
                                    'qty' => $qty,
                                    'keterangan' => $request->input("{$kategori}_ket_{$step}")[$i] ?? null,
                                ]);
                            } else {
                                ProductionMaterial::create([
                                    'production_batch_id' => $batch->id,
                                    'step' => $step,
                                    'kategori' => $kategori,
                                    'tipe' => $request->input("{$kategori}_tipe_{$step}")[$i] ?? null,
                                    'jenis' => $request->input("{$kategori}_jenis_{$step}")[$i] ?? null,
                                    'qty' => $qty,
                                    'keterangan' => $request->input("{$kategori}_ket_{$step}")[$i] ?? null,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('production_batches.index')->with('success', 'Batch berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $batch = ProductionBatch::findOrFail($id);
            $batch->materials()->delete();  // Hapus semua bahan baku terkait
            $batch->delete();  // Hapus batch
            return redirect()->route('production_batches.index')->with('success', 'Batch berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus batch: ' . $e->getMessage());
        }
    }

    public function finishProduction(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:production_batches,id',
            'hasil_status' => 'required|in:OK,BS',
            'packaging' => 'required|array|min:1',
            'packaging.*' => 'required|in:drum,pail,pot',
            'size' => 'required|array',
            'size.*' => 'required|numeric',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:1',
        ]);

        // Validasi panjang array harus sama
        if (
            count($request->packaging) !== count($request->size) ||
            count($request->size) !== count($request->quantity)
        ) {
            return back()->withErrors(['message' => 'Jumlah packaging, size, dan quantity harus sama.'])->withInput();
        }

        DB::beginTransaction();

        try {
            $batch = ProductionBatch::findOrFail($request->batch_id);
            $materials = ProductionMaterial::where('production_batch_id', $batch->id)->get();
            $location = WarehouseLocation::where('code', 'WH-001')->firstOrFail();
            $locationId = $location->id;

            $itemCache = [];

            // Validasi stok cukup
            foreach ($materials as $material) {
                $itemKey = strtolower($material->kategori) . '-' . ($material->tipe ?? $material->jenis);
                
                if (!isset($itemCache[$itemKey])) {
                    if (strtolower($material->kategori) === 'oli') {
                        $item = WarehouseItem::where('type', $material->tipe)->first();
                    } elseif (strtolower($material->kategori) === 'kapur') {
                        $item = WarehouseItem::where('name', $material->kategori)->first();
                    } else {
                        $item = WarehouseItem::where('type', $material->jenis)->first();
                    }

                    if (!$item) {
                        throw new \Exception("Item gudang untuk bahan {$material->jenis} (kategori: {$material->kategori}) tidak ditemukan.");
                    }

                    $itemCache[$itemKey] = $item;
                } else {
                    $item = $itemCache[$itemKey];
                }

                $stock = WarehouseStock::where('warehouse_item_id', $item->id)
                    ->where('warehouse_location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $material->qty) {
                    throw new \Exception("Stok tidak cukup untuk bahan {$material->jenis}. Dibutuhkan {$material->qty}, tersedia " . ($stock->quantity ?? 0));
                }
            }

             // === Validasi stok kemasan ===
            foreach ($request->packaging as $index => $pack) {
                $size = $request->size[$index];
                $qty = $request->quantity[$index];
                $key = "{$pack} {$size}Kg";
                if (!isset($itemCache[$key])) {
                    $item = WarehouseItem::where('name', $key)->first();
                    dd($item);
                    if (!$item) {
                        throw new \Exception("Item kemasan {$pack} ukuran {$size} tidak ditemukan di data gudang.");
                    }

                    $itemCache[$key] = $item;
                } else {
                    $item = $itemCache[$key];
                }

                $stock = WarehouseStock::where('warehouse_item_id', $item->id)
                    ->where('warehouse_location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $qty) {
                    throw new \Exception("Stok kemasan {$pack} ukuran {$size} tidak cukup. Dibutuhkan {$qty}, tersedia " . ($stock->quantity ?? 0));
                }
            }

            // Kurangi stok
            foreach ($materials as $material) {
                $itemKey = strtolower($material->kategori) . '-' . ($material->tipe ?? $material->jenis);
                $item = $itemCache[$itemKey];

                $stock = WarehouseStock::where('warehouse_item_id', $item->id)
                    ->where('warehouse_location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                $beforeQty = $stock->quantity;
                $afterQty = $beforeQty - $material->qty;

                $stock->update(['quantity' => $afterQty]);

                WarehouseStockMutation::create([
                    'warehouse_item_id'     => $item->id,
                    'warehouse_location_id' => $locationId,
                    'type'                  => 'out',
                    'quantity'              => $material->qty,
                    'quantity_before'       => $beforeQty,
                    'quantity_after'        => $afterQty,
                    'note'                  => 'Pengurangan bahan untuk produksi Batch #' . $batch->batch_code,
                    'source'                => 'production',
                ]);
            }

            // Simpan packaging hasil produksi
            foreach ($request->packaging as $index => $pack) {
                ProductionPackaging::create([
                    'production_batch_id' => $batch->id,
                    'packaging'           => $pack,
                    'size'                => $request->size[$index],
                    'quantity'            => $request->quantity[$index],
                ]);
            }

            // Update status produksi
            $batch->update([
                'hasil_status' => $request->hasil_status,
                'status'       => 'Closed',
            ]);

            DB::commit();

            return redirect()->route('production_batches.index')
                ->with('success', 'Produksi selesai. Stok bahan dikurangi dan data packaging disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyelesaikan produksi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['message' => 'Gagal menyelesaikan produksi: ' . $e->getMessage()])
                ->withInput();
        }
    }


    // Tampilkan detail produksi
    public function show($id)
    {
        $batch = ProductionBatch::with(['materials', 'packagingSizes'])->findOrFail($id);

        // Group bahan baku berdasarkan step dan kategori (sama seperti edit)
        $groupedMaterials = $batch->materials->groupBy(function ($item) {
            return $item->step;
        })->map(function ($items) {
            return $items->groupBy('kategori');
        });

        // Hasil produksi hanya ditampilkan jika status sudah "Closed"
        $hasilProduksi = null;
        if ($batch->status === 'Closed') {
            $hasilProduksi = $batch->packagingSizes->map(function ($item) {
                return [
                    'packaging' => $item->packaging,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'total_kg' => $item->size * $item->quantity,
                ];
            });
        }

        return view('general.produksi.view', compact('batch', 'groupedMaterials', 'hasilProduksi'));
    }


    public function forecastData(Request $request)
    {
        $produk = $request->input('produk');
        $packagings = $request->input('packagings'); // array
        $sizes = $request->input('sizes');           // array
        $quantities = $request->input('quantities'); // array

        // Validasi dasar
        if (!$produk || !$packagings || !$sizes || !$quantities) {
            return redirect()->back()->with('error', 'Data packaging tidak lengkap.');
        }

        // WarehuseData
        $warehouseStocks = DB::table('warehouse_items as wi')
            ->leftJoin('warehouse_stocks as ws', 'wi.id', '=', 'ws.warehouse_item_id')
            ->select('wi.name', 'wi.type', DB::raw('SUM(ws.quantity) as total_qty'))
            ->groupBy('wi.name', 'wi.type')
            ->get()
            ->mapWithKeys(function ($item) {
                // buat key seperti: oli-bahan atau emulsifier-bahan
                $key = strtolower($item->name) . '-' . strtolower($item->type);
                return [$key => $item->total_qty];
            });

        // Hitung total target quantity berdasarkan input user
        $totalTargetQuantity = 0;
        foreach ($quantities as $index => $qty) {
            $size = isset($sizes[$index]) ? (float)$sizes[$index] : 0;
            $qty = (float)$qty;
            $totalTargetQuantity += ($size * $qty);
        }

        $packagingInfo = [];
        foreach ($packagings as $index => $packaging) {
            $size = isset($sizes[$index]) ? (float)$sizes[$index] : 0;
            $quantity = isset($quantities[$index]) ? (int)$quantities[$index] : 0;

            // Simpan informasi packaging yang dimasukkan pengguna
            $packagingInfo[] = [
                'packaging' => $packaging,
                'size' => $size,
                'quantity' => $quantity,
                'total_kg' => $size * $quantity,
            ];
        }

        // Ambil data material dan packaging historis
        $materials = DB::table('production_materials as pm')
            ->join('production_packaging as pp', 'pm.production_batch_id', '=', 'pp.production_batch_id')
            ->join('production_batches as pb', 'pm.production_batch_id', '=', 'pb.id')
            ->where('pb.produk', $produk)
            ->where('pp.quantity', '>', 0)
            ->select('pm.kategori', 'pm.jenis', 'pm.tipe', 'pm.qty', 'pp.quantity', 'pp.size')
            ->get();

        if ($materials->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data produksi historis untuk produk ini.');
        }

        // Group berdasarkan kategori, jenis, dan tipe
        $grouped = $materials->groupBy(function ($item) {
            return $item->kategori . '-' . $item->jenis . '-' . $item->tipe;
        });

        $predictedMaterials = [];
        foreach ($grouped as $key => $rows) {
            $totalQty = 0;
            $totalOutput = 0;
        
            foreach ($rows as $row) {
                $totalQty += $row->qty;
                $totalOutput += $row->quantity * $row->size;
            }
        
            if ($totalOutput > 0) {
                $ratio = $totalQty / $totalOutput;
                $predictedQty = $ratio * $totalTargetQuantity;
        
                [$kategori, $jenis, $tipe] = explode('-', $key);
        
                // Penyesuaian pencocokan key
                if (strtolower($kategori) === 'oli') {
                    $lookupKey = strtolower($kategori) . '-' . strtolower($tipe); // name = kategori
                } else {
                    $lookupKey = strtolower($jenis) . '-' . strtolower($tipe);    // name = jenis
                }
        
                $stok = $warehouseStocks[$lookupKey] ?? 0;
                $kekurangan = max(0, $predictedQty - $stok);
        
                $predictedMaterials[] = [
                    'kategori' => $kategori,
                    'jenis' => $jenis,
                    'tipe' => $tipe,
                    'predicted_qty' => round($predictedQty, 2),
                    'stok_gudang' => round($stok, 2),
                    'kekurangan' => round($kekurangan, 2),
                ];
            }
        }
        

        $predictedMaterials = collect($predictedMaterials);

        $batchFuels = DB::table('production_batches as pb')
            ->join('production_packaging as pp', 'pb.id', '=', 'pp.production_batch_id')
            ->where('pb.produk', $produk)
            ->where('pp.quantity', '>', 0)
            ->select('pb.qty_bahan_bakar_masak', 'pb.qty_bahan_bakar_olah', 'pp.size', 'pp.quantity')
            ->groupBy('pb.id', 'pb.qty_bahan_bakar_masak', 'pb.qty_bahan_bakar_olah', 'pp.size', 'pp.quantity')
            ->get();

        $totalFuelMasak = 0;
        $totalFuelOlah = 0;
        $totalOutput = 0;

        foreach ($batchFuels as $batch) {
            $output = $batch->quantity * $batch->size;
            $totalOutput += $output;
            $totalFuelMasak += $batch->qty_bahan_bakar_masak;
            $totalFuelOlah += $batch->qty_bahan_bakar_olah;
        }

        // Hitung prediksi fuel berdasarkan total target output
        $predictedFuelMasak = $totalOutput > 0 ? round($totalFuelMasak / $totalOutput * $totalTargetQuantity, 2) : 0;
        $predictedFuelOlah = $totalOutput > 0 ? round($totalFuelOlah / $totalOutput * $totalTargetQuantity, 2) : 0;

        // Ambil stok solar dari warehouse
        $solarItem = DB::table('warehouse_items')
            ->whereRaw('LOWER(name) = ?', ['solar'])
            ->first();

        $solarStock = 0;
        if ($solarItem) {
            $solarStock = DB::table('warehouse_stocks')
                ->where('warehouse_item_id', $solarItem->id)
                ->sum('quantity');
        }

        // Total prediksi kebutuhan fuel (masak + olah)
        $totalPredictedFuel = $predictedFuelMasak + $predictedFuelOlah;

        // Hitung kekurangan solar
        $solarKekurangan = $totalPredictedFuel > $solarStock 
            ? round($totalPredictedFuel - $solarStock, 2) 
            : 0;

         // Cek apakah ada kekurangan bahan di predictedMaterials
        $adaKekurangan = false;
        foreach ($predictedMaterials as $item) {
            if ($item['kekurangan'] > 0) {
                $adaKekurangan = true;
                break;
            }
        }
        // Cek juga kekurangan solar
        if ($solarKekurangan > 0) {
            $adaKekurangan = true;
        }

        return view('general.produksi.forecast2.index', [
            'predictedMaterials' => $predictedMaterials,
            'produk' => $produk,
            'targetQuantity' => $totalTargetQuantity,
            'predictedFuelMasak' => $predictedFuelMasak,
            'predictedFuelOlah' => $predictedFuelOlah,
            'solarStock' => $solarStock,
            'solarKekurangan' => $solarKekurangan,
            'totalPredictedFuel' => $totalPredictedFuel,
            'packagingInfo' => $packagingInfo,
            'adaKekurangan' => $adaKekurangan,
        ]);
    }

    public function productionTrendPerProduct(Request $request)
    {
        $produkFilter = $request->input('produk');
        $from = $request->input('from');
        $to = $request->input('to');
        $groupBy = $request->input('group_by', 'monthly'); // default: monthly
        $compareMode = $request->input('compare_mode'); // product | date
        $produkCompare = $request->input('produk_compare');
        $compareFrom = $request->input('compare_from');
        $compareTo = $request->input('compare_to');

        $periodFormat = $groupBy === 'daily' ? "%Y-%m-%d" : "%Y-%m";

        // Data Utama
        $rawData = $this->getProductionData($produkFilter, $from, $to, $periodFormat);
        $breakdownData = $this->getBreakdownData($produkFilter, $from, $to);

        $produkList = $rawData->pluck('produk')->unique()->values();
        $labels = $rawData->pluck('period')->unique()->sort()->values();
        $datasets = [];

        foreach ($produkList as $produk) {
            $dataPerProduk = [];

            foreach ($labels as $period) {
                $value = $rawData
                    ->where('produk', $produk)
                    ->where('period', $period)
                    ->pluck('total_ton')
                    ->first() ?? 0;

                $dataPerProduk[] = $value;
            }

            $datasets[] = [
                'label' => $produk,
                'data' => $dataPerProduk,
                'backgroundColor' => 'rgba(' . rand(0,255) . ',' . rand(0,255) . ',' . rand(0,255) . ',0.6)',
            ];
        }

        // 🔹 Data Pembanding
        $compareLabels = collect();
        $compareDatasets = [];

        if ($compareMode === 'product' && $produkCompare) {
            $compareData = $this->getProductionData($produkCompare, $from, $to, $periodFormat);
            $compareLabels = $compareData->pluck('period')->unique()->sort()->values();
            $compareDatasets[] = [
                'label' => $produkCompare . ' (Pembanding)',
                'data' => $compareLabels->map(fn($period) =>
                    $compareData->where('period', $period)->pluck('total_ton')->first() ?? 0
                ),
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'type' => 'line',
            ];
        } elseif ($compareMode === 'date' && $compareFrom && $compareTo) {
            $compareData = $this->getProductionData($produkFilter, $compareFrom, $compareTo, $periodFormat);
            $compareLabels = $compareData->pluck('period')->unique()->sort()->values();
            $compareDatasets[] = [
                'label' => $produkFilter . ' (Periode Lain)',
                'data' => $compareLabels->map(fn($period) =>
                    $compareData->where('period', $period)->pluck('total_ton')->first() ?? 0
                ),
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'type' => 'line',
            ];
        }

        // Kapasitas Produksi

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Ambil total QTY dari material yang diproduksi hari ini
        $produksiHariIni = ProductionMaterial::whereHas('batch', function ($query) use ($today) {
            $query->whereDate('tanggal', $today);
        })->sum('qty');

        // Ambil total QTY dari material yang diproduksi kemarin
        $produksiKemarin = ProductionMaterial::whereHas('batch', function ($query) use ($yesterday) {
            $query->whereDate('tanggal', $yesterday);
        })->sum('qty');

        // Hitung persentase perubahan harian
        // Hindari pembagian dengan nol jika kemarin tidak ada produksi
        if ($produksiKemarin > 0) {
            $persentasePerubahanHarian = (($produksiHariIni - $produksiKemarin) / $produksiKemarin) * 100;
        } else {
            // Jika kemarin 0, dan hari ini ada produksi, anggap naik 100%. Jika sama-sama 0, tidak ada perubahan.
            $persentasePerubahanHarian = $produksiHariIni > 0 ? 100 : 0;
        }


        // --- 2. Kalkulasi Produksi Bulan Ini & Bulan Lalu ---
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonthNoOverflow()->endOfMonth();

        // Ambil total QTY bulan ini
        $produksiBulanIni = ProductionMaterial::whereHas('batch', function ($query) use ($startOfThisMonth) {
            $query->where('tanggal', '>=', $startOfThisMonth);
        })->sum('qty');

        // Ambil total QTY bulan lalu
        $produksiBulanLalu = ProductionMaterial::whereHas('batch', function ($query) use ($startOfLastMonth, $endOfLastMonth) {
            $query->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth]);
        })->sum('qty');

        // Hitung persentase perubahan bulanan
        if ($produksiBulanLalu > 0) {
            $persentasePerubahanBulanan = (($produksiBulanIni - $produksiBulanLalu) / $produksiBulanLalu) * 100;
        } else {
            $persentasePerubahanBulanan = $produksiBulanIni > 0 ? 100 : 0;
        }

        // Grafik Kapasitas
        $kapasitasHarianKg = 23; // Contoh: Ton
        $persentaseBatasAtas = 75; // Contoh: 80%
        $nilaiBatasAtasKg = $kapasitasHarianKg * ($persentaseBatasAtas / 100);

        // 2. Ambil data produksi 30 hari terakhir
        $produksiPerHari = ProductionMaterial::select(
            DB::raw('DATE(production_batches.tanggal) as tanggal_produksi'),
            DB::raw('SUM(qty) as total_qty')
        )
        ->join('production_batches', 'production_materials.production_batch_id', '=', 'production_batches.id')
        ->where('production_batches.tanggal', '>=', Carbon::now()->subDays(30))
        ->groupBy('tanggal_produksi')
        ->orderBy('tanggal_produksi', 'asc')
        ->get();

        // Format untuk Chart.js
        $chartLabelskapasitas = [];
        $chartDatakapasitasTon = [];
        $chartDataketercapaian = [];

        foreach ($produksiPerHari as $data) {
            $chartLabelskapasitas[] = $data->tanggal_produksi;
            $ton = round($data->total_qty / 1000, 2); // Konversi ke Ton
            $chartDatakapasitasTon[] = $ton;
            $persen = round(($data->total_qty / $kapasitasHarianKg) * 100, 2);
            $chartDataketercapaian[] = $persen;
        }

        // 3. Format data untuk Chart.js
        $chartLabelskapasitas = $produksiPerHari->pluck('tanggal_produksi');
        $chartDatakapasitas = $produksiPerHari->pluck('total_qty');

        return view('general.produksi.dashboard.index', [
            'labels' => $labels,
            'datasets' => $datasets,
            'produkOptions' => ProductionBatch::select('produk')->distinct()->pluck('produk'),
            'selectedProduk' => $produkFilter,
            'from' => $from,
            'to' => $to,
            'groupBy' => $groupBy,
            'breakdownData' => $breakdownData,
            'compareMode' => $compareMode,
            'compareLabels' => $compareLabels,
            'compareDatasets' => $compareDatasets,
            'produksiHariIni' => $produksiHariIni / 1000,
            'persentasePerubahanHarian' => $persentasePerubahanHarian,
            'produksiBulanIni' => $produksiBulanIni / 1000,
            'persentasePerubahanBulanan' => $persentasePerubahanBulanan,
            'produksiBulanLalu' => $produksiBulanLalu / 1000,

            // Data untuk chart
            'chartLabels' => $chartLabelskapasitas,
            'chartData' => $chartDatakapasitas,
            'nilaiBatasAtasKg' => $nilaiBatasAtasKg,
            'persentaseBatasAtas' => $persentaseBatasAtas,
            'chartLabelskapasitas' => $chartLabelskapasitas,
            'chartDatakapasitasTon' => $chartDatakapasitasTon,
            'chartDataketercapaian' => $chartDataketercapaian,
            'maxCapacity' => $kapasitasHarianKg,
        ]);
    }


    public function exportBreakdownToPdf(Request $request)
    {
        $produkFilter = $request->input('produk');
        $from = $request->input('from');
        $to = $request->input('to');
    
        $breakdown = ProductionBatch::select(
                'production_batches.produk',
                'pp.size',
                'pp.packaging as kemasan',
                DB::raw('SUM(pp.quantity) as total_unit'),
                DB::raw('SUM(pp.size * pp.quantity) / 1000 as total_ton')
            )
            ->join('production_packaging as pp', 'pp.production_batch_id', '=', 'production_batches.id')
            ->where('production_batches.status', 'Closed');
    
        if ($produkFilter) {
            $breakdown->where('produk', $produkFilter);
        }
    
        if ($from) {
            $breakdown->whereDate('production_batches.created_at', '>=', $from);
        }
    
        if ($to) {
            $breakdown->whereDate('production_batches.created_at', '<=', $to);
        }
    
        $breakdownData = $breakdown
            ->groupBy('produk', 'pp.size', 'pp.packaging')
            ->orderBy('produk')
            ->orderBy('pp.size')
            ->get();
    
        $pdf = Pdf::loadView('general.produksi.report.pdf_produksi', [
            'breakdownData' => $breakdownData,
            'selectedProduk' => $produkFilter,
            'from' => $from,
            'to' => $to,
        ])->setPaper('a4', 'portrait');
    
        return $pdf->download('ringkasan-produksi.pdf');
    }

    private function getProductionData($produk, $from, $to, $periodFormat)
    {
        return ProductionBatch::select(
                DB::raw("DATE_FORMAT(production_batches.created_at, '$periodFormat') as period"),
                'produk',
                DB::raw("SUM(pp.size * pp.quantity) / 1000 as total_ton")
            )
            ->join('production_packaging as pp', 'pp.production_batch_id', '=', 'production_batches.id')
            ->where('production_batches.status', 'Closed')
            ->when($produk, fn($q) => $q->where('produk', $produk))
            ->when($from, fn($q) => $q->whereDate('production_batches.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('production_batches.created_at', '<=', $to))
            ->groupBy('period', 'produk')
            ->orderBy('period')
            ->get();
    }

    private function getBreakdownData($produk, $from, $to)
    {
        return ProductionBatch::select(
                'production_batches.produk',
                'pp.size',
                'pp.packaging as kemasan',
                DB::raw('SUM(pp.quantity) as total_unit'),
                DB::raw('SUM(pp.size * pp.quantity) / 1000 as total_ton')
            )
            ->join('production_packaging as pp', 'pp.production_batch_id', '=', 'production_batches.id')
            ->where('production_batches.status', 'Closed')
            ->when($produk, fn($q) => $q->where('produk', $produk))
            ->when($from, fn($q) => $q->whereDate('production_batches.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('production_batches.created_at', '<=', $to))
            ->groupBy('produk', 'pp.size', 'pp.packaging')
            ->orderBy('produk')
            ->orderBy('pp.size')
            ->get();
    }

}
