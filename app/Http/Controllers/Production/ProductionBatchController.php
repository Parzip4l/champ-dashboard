<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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
    public function show(ProductionBatch $productionBatch)
    {
        $productionBatch->load('materials.tanks');
        return view('production_batches.show', compact('productionBatch'));
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

    // Hitung total target quantity berdasarkan input user
    $totalTargetQuantity = 0;
    foreach ($quantities as $index => $qty) {
        $size = isset($sizes[$index]) ? (float)$sizes[$index] : 0;
        $qty = (float)$qty;
        $totalTargetQuantity += ($size * $qty);
    }

    // Ambil data material dan packaging historis
    $materials = DB::table('production_materials as pm')
        ->join('production_packaging as pp', 'pm.production_batch_id', '=', 'pp.production_batch_id')
        ->join('production_batches as pb', 'pm.production_batch_id', '=', 'pb.id')
        ->where('pb.produk', $produk)
        ->where('pp.quantity', '>', 0)
        ->select('pm.kategori', 'pm.jenis', 'pm.tipe', 'pm.qty', 'pp.quantity')
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
            $totalOutput += $row->quantity;
        }

        if ($totalOutput > 0) {
            $ratio = $totalQty / $totalOutput;
            $predictedQty = $ratio * $totalTargetQuantity;

            list($kategori, $jenis, $tipe) = explode('-', $key);

            $predictedMaterials[] = [
                'nama' => $kategori,
                'jenis' => $jenis,
                'tipe' => $tipe,
                'predicted_qty' => round($predictedQty, 2),
            ];
        }
    }

    $predictedMaterials = collect($predictedMaterials);

    return view('general.produksi.forecast2.index', [
        'predictedMaterials' => $predictedMaterials,
        'produk' => $produk,
        'targetQuantity' => $totalTargetQuantity,
    ]);
}





}
