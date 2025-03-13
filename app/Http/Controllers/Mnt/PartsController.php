<?php

namespace App\Http\Controllers\Mnt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Model
use App\Models\Mnt\ItemModel;
use App\Models\Mnt\PartModel;

class PartsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $parts = PartModel::with('item') // Load item terkait
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })
            ->paginate(5);

        if ($request->ajax()) {
            return view('general.maintenance.parts.index', compact('parts'))->render();
        }

        return view('general.maintenance.parts.index', compact('parts'));
    }

    public function create()
    {
        $items = ItemModel::select('id', 'name')->get(); // Ambil daftar item
        return view('general.maintenance.parts.create', compact('items'));
    }

    public function store(Request $request)
    {
        try {
            // Validasi data input
            $request->validate([
                'item_id' => 'required|exists:mnt_item,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Simpan data part
            $part = PartModel::create([
                'item_id' => $request->item_id,
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->route('maintenance.part.index')->with('success', 'Part berhasil ditambahkan!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $part = PartModel::findOrFail($id);
        return view('general.maintenance.parts.edit', compact('part'));
    }

    public function update(Request $request, $id)
    {
        $part = PartModel::findOrFail($id);
    
        $part->update($request->only(['name', 'description']));
    
        return redirect()->route('maintenance.part.index')->with('success', 'Item updated successfully!');
    }

    public function destroy($id)
    {
        try {
            // Cari menu berdasarkan ID
            $part = PartModel::findOrFail($id);
            
            $part->delete();

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
}
