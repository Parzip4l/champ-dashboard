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
use App\Models\Setting\Slack;
use App\Models\User;

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

        // Cek apakah jadwal maintenance tersedia
        $schedule = ScheduleModel::where('item_id', $request->item_id)
            ->whereDate('next_maintenance', now()->toDateString())
            ->first();

        if (!$schedule) {
            return redirect()->route('maintenance.item.index')->with('error', 'Belum Waktunya Maintenance Untuk Item Ini.');
        }

        $checklistData = [];

        // Simpan log untuk setiap checklist
        foreach ($request->checklist_ids as $checklist_id) {
            LogModel::create([
                'maintenance_id' => $schedule->id,
                'item_id' => $request->item_id,
                'part_id' => $request->part_id,
                'performed_at' => $request->performed_at,
                'maintenance_by' => $request->maintenance_by,
                'checklist_item' => $request->checklist_items[$checklist_id] ?? '',
                'status' => $request->checklist_status[$checklist_id],
                'notes' => $request->checklist_notes[$checklist_id],
            ]);

            $checklistData[] = [
                'title' => $request->checklist_items[$checklist_id] ?? 'N/A',
                'value' => "Status: {$request->checklist_status[$checklist_id]} | Catatan: {$request->checklist_notes[$checklist_id]}",
                'short' => false,
            ];
        }

        // Kirim notifikasi ke Slack
        $slackChannel = Slack::where('channel', 'maintenance')->first();
        if ($slackChannel) {
            $slackWebhookUrl = $slackChannel->url;
            $item = ItemModel::find($request->item_id);
            $part = PartModel::find($request->part_id);
            $user = User::find($request->maintenance_by);
            $data = [
                'text' => "Data Maintenance Log",
                'attachments' => [[
                    'title' => 'Maintenance Details',
                    'fields' => array_merge([
                        [
                            'title' => 'Item',
                            'value' => $item ? $item->name : 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Parts',
                            'value' => $part ? $part->name : 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Tanggal',
                            'value' => now()->format('d F Y'),
                            'short' => true,
                        ],
                        [
                            'title' => 'Maintenance By',
                            'value' => $user ? $user->name : 'Unknown',
                            'short' => true,
                        ]
                    ], $checklistData),
                ]],
            ];

            $data_string = json_encode($data);
            $ch = curl_init($slackWebhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result === false || $httpCode !== 200) {
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack.');
            }
        }

        // Update `next_maintenance` berdasarkan tipe maintenance
        $nextMaintenanceDate = match ($schedule->schedule) {
            'daily' => now()->addDay()->toDateString(),
            'weekly' => now()->addWeek()->toDateString(),
            'monthly' => now()->addMonth()->toDateString(),
            'yearly' => now()->addYear()->toDateString(),
            default => now()->toDateString(),
        };

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
