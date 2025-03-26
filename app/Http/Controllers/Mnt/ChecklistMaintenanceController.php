<?php

namespace App\Http\Controllers\Mnt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Mnt\ItemModel;
use App\Models\Mnt\PartModel;
use App\Models\Mnt\ChecklistModel;

class ChecklistMaintenanceController extends Controller
{
    //

    public function index(Request $request)
    {
        $search = $request->get('search');
        $items = ChecklistModel::with(['part', 'item']) // Load relasi Part & Item
                ->when($search, function ($query, $search) {
                    return $query->where('checklist_item', 'like', '%' . $search . '%');
                })
                ->orderBy('part_id')
                ->paginate(5);

            if ($request->ajax()) {
                return view('general.maintenance.checklist.index', compact('items'))->render();
            }

            return view('general.maintenance.checklist.index', compact('items'));
    }

    public function create()
    {
        $items = PartModel::select('id', 'name', 'item_id')->get(); // Ambil daftar item
        return view('general.maintenance.checklist.create', compact('items'));
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'part_id' => 'required|exists:mnt_parts,id',
                'checklist_item' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Simpan data checklist
            $checklist = new ChecklistModel();
            $checklist->part_id = $request->part_id;
            $checklist->checklist_item = $request->checklist_item;
            $checklist->keterangan = $request->keterangan;
            $checklist->save();

            // Redirect dengan pesan sukses
            return redirect()->route('maintenance.listmaintenance.index')
                            ->with('success', 'Checklist berhasil disimpan.');

        } catch (\Exception $e) {
            // Redirect dengan pesan error jika terjadi kesalahan
            return redirect()->back()
                            ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $checklist = ChecklistModel::findOrFail($id); // Ambil data checklist berdasarkan ID
        $items = PartModel::select('id', 'name', 'item_id')->get(); // Ambil daftar part untuk dropdown

        return view('general.maintenance.checklist.edit', compact('checklist', 'items'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'part_id' => 'required|exists:mnt_parts,id',
            'checklist_item' => 'required|string|max:255',
            'keterangan' => 'string',
        ]);

        $checklist = ChecklistModel::findOrFail($id); // Cari data checklist berdasarkan ID
        $checklist->update([
            'part_id' => $request->part_id,
            'checklist_item' => $request->checklist_item,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('maintenance.listmaintenance.index')
            ->with('success', 'Checklist updated successfully!');
    }

    public function destroy($id)
    {
        try {
            // Cari menu berdasarkan ID
            $item = ChecklistModel::findOrFail($id);
            
            $item->delete();

            // Mengembalikan response JSON dengan status sukses
            return response()->json([
                'success' => true,
                'message' => 'Data has been deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Log error dan kembalikan error response JSON
            Log::error('Error deleting data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete data. Please try again later. ' . $e->getMessage()
            ], 500); // Menggunakan status code 500 jika ada error server
        }
    }

}
