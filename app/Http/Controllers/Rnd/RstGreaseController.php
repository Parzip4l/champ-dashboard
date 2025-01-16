<?php

namespace App\Http\Controllers\Rnd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Model
use App\Models\Rnd\RisetGreaseMaster;
use App\Models\Rnd\RisetGreaseDetails;

class RstGreaseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $month = $request->get('month');
        $year = $request->get('year');
        $sevenDaysLater = Carbon::today()->addDays(7);

        $reminder = RisetGreaseDetails::where('improvement_schedule', '<=', $sevenDaysLater)->get();

        // Query to get RisetGreaseMaster with search and date filters
        $data = RisetGreaseMaster::when($search, function ($query, $search) {
                return $query->where('product_name', 'like', '%' . $search . '%')
                            ->orWhere('batch_code', 'like', '%' . $search . '%');
            })
            ->when($month, function ($query, $month) {
                return $query->whereMonth('expected_start_date', $month);
            })
            ->when($year, function ($query, $year) {
                return $query->whereYear('expected_start_date', $year);
            })
            ->paginate(10);

        // If the request is AJAX, return only the view content to update the table and pagination
        if ($request->ajax()) {
            return view('general.lab.riset', compact('data','reminder'))->render(); 
        }

        // For normal view, pass data to the view
        return view('general.lab.riset', compact('data', 'search', 'month', 'year', 'reminder'));
    }

    public function create()
    {
        return view('general.lab.riset.create');
    }

    public function store(Request $request)
    {
        $batchCode = Str::random(5);

        // Get logged-in user ID (or name if necessary)
        $createdBy = Auth::user()->name;

        DB::beginTransaction(); // Mulai transaksi database

        try {
            // Simpan data ke tabel RisetGreaseMaster
            $master = new RisetGreaseMaster();
            $master->batch_code = $batchCode;
            $master->product_name = $request->input('product_name');
            $master->expected_start_date = $request->input('expected_start_date');
            $master->expected_end_date = $request->input('expected_end_date');
            $master->created_by = $createdBy;
            $master->save();

            // Simpan data ke tabel RisetGreaseDetails
            $details = $request->input('details');

            foreach ($details as $detail) {
                $detailData = new RisetGreaseDetails();
                $detailData->master_id = $master->id; // Hubungkan dengan master ID
                $detailData->trial_method = $detail['trial_method'];
                $detailData->trial_result = $detail['trial_result'];
                $detailData->issue = $detail['issue'];
                $detailData->improvement_ideas = $detail['improvement_ideas'];
                $detailData->improvement_schedule = $detail['improvement_schedule'];
                $detailData->competitor_comparison = $detail['competitor_comparison'];
                $detailData->status = $detail['status'];
                $detailData->created_by = $createdBy;
                $detailData->save();
            }

            DB::commit(); // Commit transaksi jika semuanya berhasil

            return redirect()->route('log-riset-grease.index')->with('success', 'Riset Data successfully created.');
        } catch (\Exception $e) {
            DB::rollback(); // Rollback transaksi jika terjadi error

            return redirect()->back()->with('error', 'Failed to create data: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $master = RisetGreaseMaster::with('details')->findOrFail($id);

        return view('general.lab.riset.details', compact('master'));
    }

    public function edit($id)
    {
        $master = RisetGreaseMaster::with('details')->findOrFail($id);

        return view('general.lab.riset.edit', compact('master'));
    }

    public function update(Request $request, $id)
    {
        $createdBy = Auth::user()->name;
        DB::beginTransaction();

        try {
            // Update data master
            $master = RisetGreaseMaster::findOrFail($id);
            $master->update([
                'product_name' => $request->input('product_name'),
                'expected_start_date' => $request->input('expected_start_date'),
                'expected_end_date' => $request->input('expected_end_date'),
            ]);

            // Update atau tambahkan data detail
            $details = $request->input('details', []);
            $existingDetailIds = RisetGreaseDetails::where('master_id', $id)->pluck('id')->toArray();

            foreach ($request->details as $detail) {
                if (isset($detail['id'])) {
                    // Update detail yang sudah ada
                    RisetGreaseDetails::where('id', $detail['id'])->update([
                        'trial_method' => $detail['trial_method'],
                        'trial_result' => $detail['trial_result'],
                        'issue' => $detail['issue'],
                        'improvement_ideas' => $detail['improvement_ideas'],
                        'improvement_schedule' => $detail['improvement_schedule'],
                        'competitor_comparison' => $detail['competitor_comparison'],
                        'status' => $detail['status'],
                    ]);
                } else {
                    // Tambahkan detail baru
                    RisetGreaseDetails::create([
                        'master_id' => $id,
                        'trial_method' => $detail['trial_method'],
                        'trial_result' => $detail['trial_result'],
                        'issue' => $detail['issue'],
                        'improvement_ideas' => $detail['improvement_ideas'],
                        'improvement_schedule' => $detail['improvement_schedule'],
                        'competitor_comparison' => $detail['competitor_comparison'],
                        'status' => $detail['status'],
                        'created_by' => $createdBy,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Riset Grease berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            // Log error (opsional)
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Gunakan database transaction untuk memastikan penghapusan atomik
            DB::transaction(function () use ($id) {
                // Cari data master berdasarkan ID
                $master = RisetGreaseMaster::findOrFail($id);

                // Hapus semua details terkait master
                $master->details()->delete();

                // Hapus data master
                $master->delete();
            });

            // Kirim respons JSON sukses
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            // Kirim respons JSON error jika terjadi kesalahan
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function destroyDetail($id)
    {
        $detail = RisetGreaseDetails::findOrFail($id);
        $detail->delete();

        return response()->json(['success' => true]);
    }

    
}
