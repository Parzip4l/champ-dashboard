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
use App\Models\Setting\Slack;

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
            $doneDetails = [];  // Array untuk menyimpan detail dengan status "done"

            foreach ($details as $detail) {
                if (isset($detail['id'])) {
                    // Update detail yang sudah ada
                    $updatedDetail = RisetGreaseDetails::find($detail['id']);
                    if ($updatedDetail) {
                        $updatedDetail->update([
                            'trial_method' => $detail['trial_method'],
                            'trial_result' => $detail['trial_result'],
                            'issue' => $detail['issue'],
                            'improvement_ideas' => $detail['improvement_ideas'],
                            'improvement_schedule' => $detail['improvement_schedule'],
                            'competitor_comparison' => $detail['competitor_comparison'],
                            'status' => $detail['status'],
                        ]);

                        if ($detail['status'] === 'Done') {
                            $doneDetails[] = $updatedDetail;
                        }
                    }
                } else {
                    // Tambahkan detail baru
                    $newDetail = RisetGreaseDetails::create([
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

                    if ($detail['status'] === 'Done') {
                        $doneDetails[] = $newDetail;
                    }
                }
            }

            DB::commit();

            // Kirim data ke Slack hanya jika ada detail dengan status "done"
            if (!empty($doneDetails)) {
                $slackChannel = Slack::where('channel', 'test')->first();
                $slackWebhookUrl = $slackChannel->url;

                $attachments = [];
                foreach ($doneDetails as $detail) {
                    $attachments[] = [
                        'title' => 'Detail - ' . $request->input('product_name'),
                        'fields' => [
                            [
                                'title' => 'Tanggal',
                                'value' => now()->format('d F Y'),
                                'short' => true,
                            ],
                            [
                                'title' => 'Metode',
                                'value' => $detail->trial_method,
                                'short' => true,
                            ],
                            [
                                'title' => 'Hasil Uji',
                                'value' => $detail->trial_result,
                                'short' => true,
                            ],
                            [
                                'title' => 'Ide Improvment',
                                'value' => $detail->improvement_ideas,
                                'short' => true,
                            ],
                            [
                                'title' => 'Jadwal Improvment',
                                'value' => $detail->improvement_schedule,
                                'short' => true,
                            ],
                            [
                                'title' => 'Kompetitor',
                                'value' => $detail->competitor_comparison,
                                'short' => true,
                            ],
                            [
                                'title' => 'Status',
                                'value' => $detail->status,
                                'short' => true,
                            ],
                            [
                                'title' => 'Created By',
                                'value' => $createdBy,
                                'short' => true,
                            ],
                        ],
                    ];
                }

                $data = [
                    'text' => "Log Report Formulation",
                    'attachments' => $attachments,
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

                if ($result === false) {
                    $error = curl_error($ch);
                    return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack: ' . $error);
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode !== 200) {
                    return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack. Kode status: ' . $httpCode);
                }

                curl_close($ch);
            }

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

    public function generateReport(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'startMonth' => 'required|numeric',
                'startYear' => 'required|numeric',
                'endMonth' => 'required|numeric',
                'endYear' => 'required|numeric',
            ]);

            // Ambil data bulan dan tahun dari form
            $startMonth = $request->startMonth;
            $startYear = $request->startYear;
            $endMonth = $request->endMonth;
            $endYear = $request->endYear;

            // Tentukan tanggal mulai dan tanggal akhir berdasarkan bulan dan tahun
            $startDate = Carbon::createFromFormat('Y-m-d', "{$startYear}-{$startMonth}-01");
            $endDate = Carbon::createFromFormat('Y-m-d', "{$endYear}-{$endMonth}-01")->endOfMonth();

            // Ambil data dari RisetGreaseMaster berdasarkan rentang tanggal
            $risetData = RisetGreaseMaster::whereBetween('expected_start_date', [$startDate, $endDate])
                ->get();

            // Untuk setiap riset, ambil jumlah detail riset
            $risetDataWithDetails = $risetData->map(function ($riset) {
                // Ambil jumlah detail untuk riset ini
                $detailsCount = RisetGreaseDetails::where('master_id', $riset->id)->count();
                // Tambahkan jumlah detail ke objek riset
                $riset->details_count = $detailsCount;
                return $riset;
            });

            // Kirim data ke view
            return view('general.lab.riset.report', compact('risetDataWithDetails', 'startDate', 'endDate'));
            
        } catch (Exception $e) {
            // Tangkap error jika terjadi
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
}
