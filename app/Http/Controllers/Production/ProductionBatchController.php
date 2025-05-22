<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

// Model
use App\Models\Production\ProductionMaterialTank;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionMaterial;
use App\Models\Production\ProductionPackaging;


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

        // Temukan batch berdasarkan batch_id
        $batch = ProductionBatch::findOrFail($request->batch_id);

        // Perbarui hasil_status dan status produksi
        $batch->hasil_status = $request->hasil_status;
        $batch->status = 'Closed';
        $batch->save();

        // Simpan setiap kombinasi packaging, size, quantity
        foreach ($request->packaging as $index => $pack) {
            ProductionPackaging::create([
                'production_batch_id' => $batch->id,
                'packaging' => $pack,
                'size' => $request->size[$index],
                'quantity' => $request->quantity[$index],
            ]);
        }

        return redirect()->route('production_batches.index')
            ->with('success', 'Produksi selesai dan semua data packaging disimpan.');
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
