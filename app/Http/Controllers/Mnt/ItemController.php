<?php

namespace App\Http\Controllers\Mnt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Model
use App\Models\Mnt\ItemModel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;


class ItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $items = ItemModel::with('parts', 'maintenances')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            })
            ->paginate(5)
            ->through(function ($item) {
                $item->total_parts = $item->parts->count();
                return $item;
            });

        return view ('general.maintenance.item.index', compact('items'));
    }

    public function create()
    {
        return view('general.maintenance.item.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    
        // Buat item tanpa QR Code terlebih dahulu
        $item = ItemModel::create([
            'name' => $request->name,
            'description' => $request->description,
            'qr_code' => '', // Sementara kosong
            'qr_code_path' => '',
        ]);
    
        // Generate QR Code dengan URL spesifik untuk item ini
        $qrCodeData = url("/maintenance/form/{$item->id}");
        $qrCodePath = "qrcodes/item_{$item->id}.svg";
    
        // Simpan QR Code ke penyimpanan publik
        Storage::disk('public')->put($qrCodePath, 
            QrCode::format('svg')->size(200)->errorCorrection('H')->generate($qrCodeData)
        );
    
        // Update item dengan path QR Code
        $item->update([
            'qr_code' => $qrCodeData,
            'qr_code_path' => $qrCodePath
        ]);

        return redirect()->route('maintenance.item.index')->with('success', 'Item created successfully!');
    }

    public function edit($id)
    {
        $item = ItemModel::findOrFail($id);
        return view('general.maintenance.item.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ItemModel::findOrFail($id);
    
        $item->update($request->only(['name', 'description']));
    
        return redirect()->route('maintenance.item.index')->with('success', 'Item updated successfully!');
    }

    public function destroy($id)
    {
        try {
            // Cari menu berdasarkan ID
            $item = ItemModel::findOrFail($id);
            if ($item->qr_code_path) {
                Storage::disk('public')->delete($item->qr_code_path);
            }
            
            $item->delete();

            // Mengembalikan response JSON dengan status sukses
            return response()->json([
                'success' => true,
                'message' => 'Menu has been deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Log error dan kembalikan error response JSON
            Log::error('Error deleting menu: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu. Please try again later. ' . $e->getMessage()
            ], 500); // Menggunakan status code 500 jika ada error server
        }
    }

    public function downloadQrCode($id)
    {
        $item = ItemModel::findOrFail($id);
        
        if (!$item->qr_code_path || !Storage::disk('public')->exists($item->qr_code_path)) {
            return redirect()->route('items.index')->with('error', 'QR Code not found.');
        }

        return response()->download(storage_path("app/public/{$item->qr_code_path}"));
    }
}
