<?php

namespace App\Http\Controllers\Mnt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mnt\ItemModel;
use App\Models\Mnt\PartModel;
use App\Models\Mnt\ChecklistModel;
use App\Models\Mnt\LogModel;
use App\Models\Mnt\ScheduleModel;
use App\Models\Mnt\MaintenanceChecklist;

class LogMaintenanceController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->get('search');

        $maintenanceLogs = LogModel::with(['item', 'part'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('item', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })->orWhereHas('part', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            })
            ->orderBy('item_id') // Mengelompokkan berdasarkan item
            ->orderBy('part_id') // Lalu berdasarkan part
            ->paginate(10);

        return view('general.maintenance.logs.index', compact('maintenanceLogs'));
    }

    public function create(Request $request, $item_id)
    {
        $item = ItemModel::findOrFail($item_id);
        $parts = PartModel::where('item_id', $item_id)->get();
        
        // Cek apakah ada jadwal maintenance untuk hari ini
        $schedule = ScheduleModel::where('item_id', $item_id)
            ->whereDate('next_maintenance', now()->toDateString())
            ->first();

        return view('general.maintenance.form', compact('item', 'parts', 'schedule'));
    }

    // API untuk mendapatkan checklist berdasarkan part yang dipilih
    public function getChecklistByPart($partId)
    {
        $checklists = ChecklistModel::where('part_id', $partId)->get();
        return response()->json($checklists);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required',
            'part_id' => 'required',
            'performed_at' => 'required|date',
            'maintenance_by' => 'required',
            'checklist_ids' => 'array',
            'checklist_status' => 'array',
            'checklist_notes' => 'array',
        ]);

        // Simpan data maintenance log
        $schedule = ScheduleModel::where('item_id', $request->item_id)
                ->whereDate('next_maintenance', now()->toDateString())
                ->first();

            if (!$schedule) {
                return redirect()->route('maintenance.item.index')->with('error', 'Belum Waktunya Maintenance Untuk Item Ini.');
            }

            // Simpan log untuk setiap checklist
            foreach ($request->checklist_ids as $index => $checklist_id) {
                LogModel::create([
                    'maintenance_id' => $schedule->id, // Ambil ID schedule berdasarkan item_id
                    'item_id' => $request->item_id,
                    'part_id' => $request->part_id,
                    'performed_at' => $request->performed_at,
                    'maintenance_by' => $request->maintenance_by,
                    'checklist_item' => $request->checklist_items[$checklist_id] ?? '',
                    'status' => $request->checklist_status[$checklist_id],
                    'notes' => $request->checklist_notes[$checklist_id],
                ]);
            }

            // Update `next_maintenance` berdasarkan tipe maintenance
            $nextMaintenanceDate = match ($schedule->schedule) {
                'daily' => now()->addDay()->toDateString(),
                'weekly' => now()->addWeek()->toDateString(),
                'monthly' => now()->addMonth()->toDateString(),
                'yearly' => now()->addYear()->toDateString(),
                default => now()->toDateString(), // Jika tidak ada tipe yang cocok, default ke hari ini
            };

            // Update next maintenance di tabel `mnt_schedule`
            $schedule->update(['next_maintenance' => $nextMaintenanceDate]);

            return redirect()->route('maintenance.logs')->with('success', 'Maintenance log berhasil disimpan.');
    }

    public function show($id)
    {
        $maintenance = LogModel::with(['item', 'part', 'maintenanceBy'])->findOrFail($id);

        return response()->json([
            'id' => $maintenance->id,
            'item_name' => $maintenance->item->name,
            'part_name' => $maintenance->part->name,
            'checklist_item' => $maintenance->checklist_item,
            'status' => $maintenance->status,
            'notes' => $maintenance->notes,
            'maintenance_by' => $maintenance->maintenanceBy->name,
            'performed_at' => \Carbon\Carbon::parse($maintenance->performed_at)->format('d M Y H:i'),
        ]);
    }
}
