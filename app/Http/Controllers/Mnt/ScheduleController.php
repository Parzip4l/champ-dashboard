<?php

namespace App\Http\Controllers\Mnt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Model
use App\Models\Mnt\ItemModel;
use App\Models\Mnt\PartModel;
use App\Models\Mnt\ScheduleModel;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $schedules = ScheduleModel::with('item') // Load ItemModel
        ->when($search, function ($query, $search) {
            return $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        })
        ->paginate(5);

        return view ('general.maintenance.schedule.index', compact('schedules'));
    }

    public function create()
    {
        $items = ItemModel::select('id', 'name')->get(); // Ambil daftar item
        return view('general.maintenance.schedule.create', compact('items'));
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'item_id' => 'required|exists:mnt_item,id',
                'schedule' => 'required|in:Daily,Weekly,Monthly,Custom',
                'start_date' => 'nullable|date',
                'next_maintenance' => 'required|date',
            ]);

            // Simpan ke database
            ScheduleModel::create([
                'item_id' => $request->item_id,
                'schedule' => $request->schedule,
                'next_maintenance' => $request->next_maintenance,
            ]);

            return redirect()->route('maintenance.schedule.index')->with('success', 'Schedule successfully added!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save schedule. Please try again.' .$e->getMessage());
        }
    }

    public function edit($id)
    {
        $schedule = ScheduleModel::findOrFail($id);
        $items = ItemModel::all(); // Ambil semua item untuk dropdown
        return view('general.maintenance.schedule.edit', compact('schedule', 'items'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'start_date' => 'required|date',
            'schedule' => 'required|in:Daily,Weekly,Monthly,Custom',
            'next_maintenance' => 'required|date|after:start_date',
        ]);

        $schedule = ScheduleModel::findOrFail($id);
        $schedule->update($request->all());

        return redirect()->route('maintenance.schedule.index')->with('success', 'Schedule updated successfully.');
    }

    public function destroy($id)
    {
        try {
            // Cari menu berdasarkan ID
            $schedule = ScheduleModel::findOrFail($id);
            
            $schedule->delete();

            // Mengembalikan response JSON dengan status sukses
            return response()->json([
                'success' => true,
                'message' => 'Schedule has been deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Log error dan kembalikan error response JSON
            Log::error('Error deleting schedule: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule. Please try again later. ' . $e->getMessage()
            ], 500); // Menggunakan status code 500 jika ada error server
        }
    }
}
