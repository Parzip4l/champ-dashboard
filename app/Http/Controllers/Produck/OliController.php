<?php

namespace App\Http\Controllers\Produck;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product\Oli;
use Carbon\Carbon;
use App\Models\Setting\Slack;
use App\Models\Setting\HargaOli;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OliReportExport;

class OliController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->get('search');
        $filter = $request->get('filter', '1M');
        $now = Carbon::now();

        switch ($filter) {
            case '1M':
                $startDate = $now->copy()->subMonth()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case '6M':
                $startDate = $now->copy()->subMonths(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case '1Y':
                $startDate = $now->copy()->subYear()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            default:
                $startDate = Oli::min('tanggal') ?? $now->copy()->subYears(5); // Default to 5 years if no data
                $endDate = $now->copy()->endOfDay();
                break;
        }

        // Fetch oli data, grouping by month for '1Y' and '6M' filters
        if ($filter === '1Y' || $filter === '6M') {
            $oliReceived = Oli::whereBetween('tanggal', [$startDate, $endDate])
                ->selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month, jenis_oli, SUM(jumlah) as total')
                ->groupBy('year', 'month', 'jenis_oli')
                ->orderBy('year', 'asc')  // Ensure sorting is done in ascending order
                ->orderBy('month', 'asc') // Sorting by month in ascending order
                ->get();
        } else {
            // If the filter is '1M' or 'ALL', keep the previous logic (daily grouping)
            $oliReceived = Oli::whereBetween('tanggal', [$startDate, $endDate])
                ->selectRaw('DATE(tanggal) as date, jenis_oli, SUM(jumlah) as total')
                ->groupBy('date', 'jenis_oli')
                ->orderBy('date', 'asc')
                ->get();
        }

        // Prepare data for ApexCharts
        $jenisOli = ['Trafo', 'Bahan', 'Service', 'Minarex'];
        $dataSeries = [];
        $dates = [];

        // Initialize data series with 0 for each type of oil
        foreach ($jenisOli as $jenis) {
            $dataSeries[$jenis] = [];
        }

        // Create the categories (months or days depending on filter)
        if ($filter === '1Y' || $filter === '6M') {
            // Group by year and month (format as YYYY-MM)
            $oliReceived->each(function ($item) use (&$dates) {
                $monthYear = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);  // Format as YYYY-MM
                if (!in_array($monthYear, $dates)) {
                    $dates[] = $monthYear;
                }
            });
        } else {
            // Group by specific dates (for 1M or ALL) with full date (YYYY-MM-DD)
            $oliReceived->each(function ($item) use (&$dates) {
                if (!in_array($item->date, $dates)) {
                    $dates[] = $item->date;
                }
            });
        }

        // Add current month to the categories if it's '1M'
        if ($filter === '1M' && !in_array($now->format('Y-m-d'), $dates)) {
            $dates[] = $now->format('Y-m-d');  // Ensure the current day is included for '1M'
        }

        // Initialize data series with 0 for each date/month
        foreach ($jenisOli as $jenis) {
            foreach ($dates as $date) {
                $dataSeries[$jenis][] = 0;
            }
        }

        // Populate the data series with totals for each oil type
        foreach ($oliReceived as $item) {
            // For '1Y' or '6M', format as YYYY-MM, and for '1M', use the full date
            if ($filter === '1Y' || $filter === '6M') {
                $index = array_search($item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT), $dates);
            } else {
                $index = array_search($item->date, $dates);
            }

            if ($index !== false && in_array($item->jenis_oli, $jenisOli)) {
                // Round the total value to 1 decimal place
                $dataSeries[$item->jenis_oli][$index] = round((float) $item->total, 1);
            }
        }

        // Prepare data for ApexCharts
        $chartData = [
            'categories' => $dates,
            'series' => []
        ];

        // Add formatted labels for each date
        $labels = [];
        foreach ($dates as $date) {
            if ($filter === '1Y' || $filter === '6M') {
                // Use YYYY-MM format for 1Y or 6M
                $labels[] = Carbon::createFromFormat('Y-m', $date)->format('Y-m');
            } else {
                // Use YYYY-MM-DD format for 1M
                $labels[] = Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');
            }
        }

        foreach ($jenisOli as $jenis) {
            $chartData['series'][] = [
                'name' => $jenis,
                'data' => $dataSeries[$jenis],
            ];
        }

        $chartData['labels'] = $labels; // Include the formatted labels in the chart data
        

        // Rentang tanggal untuk bulan ini
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        // Rentang tanggal untuk bulan lalu
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Hitung total oli bulan ini dan bulan lalu
        $totalOliCurrent = [
            'Trafo' => Oli::where('jenis_oli', 'Trafo')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('jumlah'),
            'Bahan' => Oli::where('jenis_oli', 'Bahan')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('jumlah'),
            'Service' => Oli::where('jenis_oli', 'Service')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('jumlah'),
            'Minarex' => Oli::where('jenis_oli', 'Minarex')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('jumlah'),
        ];
        $totalOliLast = [
            'Trafo' => Oli::where('jenis_oli', 'Trafo')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('jumlah'),
            'Bahan' => Oli::where('jenis_oli', 'Bahan')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('jumlah'),
            'Service' => Oli::where('jenis_oli', 'Service')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('jumlah'),
            'Minarex' => Oli::where('jenis_oli', 'Minarex')
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('jumlah'),
        ];

        // Hitung persentase perubahan
        $percentChange = [];
        foreach ($totalOliCurrent as $key => $currentValue) {
            $lastValue = $totalOliLast[$key] ?? 0;
            if ($lastValue > 0) {
                $percentChange[$key] = (($currentValue - $lastValue) / $lastValue) * 100;
            } else {
                $percentChange[$key] = $currentValue > 0 ? 100 : 0; // 100% jika ada kenaikan dari 0
            }
        }

        // Ambil data oli lainnya jika diperlukan
        $oli = Oli::when($search, function ($query, $search) {
            return $query->where('pengirim', 'like', '%' . $search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(50);

        // Setting Harga Oli
        $hargaOli = HargaOli::all();

        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
    
        // Rentang tanggal untuk bulan sebelumnya
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
    
        // Hitung total oli bulan ini per jenis
        $totalsThisMonth = Oli::whereBetween('tanggal', [$currentMonthStart, $currentMonthEnd])
            ->selectRaw('jenis_oli, SUM(total) as total')
            ->groupBy('jenis_oli')
            ->pluck('total', 'jenis_oli')
            ->toArray();
    
        // Hitung total oli bulan sebelumnya per jenis
        $totalsLastMonth = Oli::whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->selectRaw('jenis_oli, SUM(total) as total')
            ->groupBy('jenis_oli')
            ->pluck('total', 'jenis_oli')
            ->toArray();
    
        // Pastikan semua jenis oli ada dalam array dengan nilai default 0
        $jenisOli = ['Trafo', 'Bahan', 'Service', 'Minarex'];
        foreach ($jenisOli as $jenis) {
            $totalsThisMonth[$jenis] = $totalsThisMonth[$jenis] ?? 0;
            $totalsLastMonth[$jenis] = $totalsLastMonth[$jenis] ?? 0;
        }
    
        // Hitung persentase perubahan
        $percentChangeHarga = [];
        foreach ($jenisOli as $jenis) {
            $lastMonth = $totalsLastMonth[$jenis] ?? 0;
            $thisMonth = $totalsThisMonth[$jenis] ?? 0;
            if ($lastMonth > 0) {
                $percentChangeHarga[$jenis] = (($thisMonth - $lastMonth) / $lastMonth) * 100;
            } else {
                $percentChangeHarga[$jenis] = $thisMonth > 0 ? 100 : 0;
            }
        }


        return view('general.inventory.oli.index', compact('oli', 'search', 'totalOliCurrent', 'percentChange', 'chartData', 'filter','hargaOli' ,'totalsThisMonth','percentChangeHarga'));
    }


    public function create()
    {
        return view('general.inventory.oli.form');
    }

    public function store(Request $request)
    {
        try {
            $defaultReceive = 'Not Received';
            $pengirim = $request->pengirim;
            $tanggal = now();
            $jenisOliArray = $request->jenis_oli;
            $jumlahArray = $request->jumlah;

            $attachments = [[
                'color' => '#36a64f',
                'fields' => [
                    ['title' => 'Tanggal', 'value' => $tanggal->format('d F Y'), 'short' => true],
                    ['title' => 'Pengirim', 'value' => $pengirim, 'short' => true],
                ]
            ]];

            foreach ($jenisOliArray as $index => $jenisOli) {
                $jumlah = (int)$jumlahArray[$index];

                $hargaData = HargaOli::where('jenis_oli', $jenisOli)->first();
                if (!$hargaData) continue;

                $harga = $hargaData->harga;
                $total = $harga * $jumlah;

                // Simpan ke database
                $oli = new Oli();
                $oli->tanggal = $tanggal;
                $oli->pengirim = $pengirim;
                $oli->jenis_oli = $jenisOli;
                $oli->jumlah = $jumlah;
                $oli->receive_status = $defaultReceive;
                $oli->harga = $harga;
                $oli->total = $total;
                $oli->save();

                // Tambah attachment untuk Slack
                $attachments[] = [
                    'color' => '#FFC512',
                    'fields' => [
                        ['title' => 'Jenis Oli', 'value' => $jenisOli, 'short' => true],
                        ['title' => 'Jumlah', 'value' => $jumlah, 'short' => true],
                        ['title' => 'Harga /Drum', 'value' => 'Rp ' . number_format($harga, 0, ',', '.'), 'short' => true],
                        ['title' => 'Total', 'value' => 'Rp ' . number_format($total, 0, ',', '.'), 'short' => true],
                    ]
                ];
            }

            // Tambahkan link ke portal
            $attachments[] = [
                'color' => '#eeeeee',
                'fields' => [
                    ['title' => 'Lihat Detail Data Di Champoil Portal', 'value' => '<https://dashboard.champoil.co.id/pencatatan-oli>', 'short' => false],
                ]
            ];

            // Kirim ke Slack
            $slackChannel = Slack::where('channel', 'Data Oli')->first();
            $slackWebhookUrl = $slackChannel->url;

            $data = [
                'text' => ":oil_drum: *Data Pengiriman Oli Dari {$request->pengirim}*",
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
            curl_close($ch);

            if ($httpCode !== 200) {
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack. Kode status: ' . $httpCode);
            }

            return redirect()->back()->with('success', 'Data berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan Data: ' . $e->getMessage());
        }
    }


    
    public function edit($id)
    {
        try {
            $oli = Oli::findOrFail($id);

            return view('general.inventory.oli.edit', compact('oli'));
        } catch (Exception $e) {
            // Jika terjadi error, tampilkan pesan error
            return redirect()->route('users.seller.list')->with('error', 'Distributor not found!');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi data yang diterima dari form
            $validated = $request->validate([
                'pengirim' => 'required|string|max:255',
                'jenis_oli' => 'required|string|max:255',
                'jumlah' => 'required|string|max:255',
                'harga' => 'required|numeric',
                'total' => 'required|numeric',
            ]);

            // Ambil data oli yang akan diupdate berdasarkan ID
            $oli = Oli::findOrFail($id);

            // Update data oli dengan data dari form
            $oli->pengirim = $validated['pengirim'];
            $oli->jenis_oli = $validated['jenis_oli'];
            $oli->jumlah = $validated['jumlah'];
            $oli->harga = $validated['harga'];
            $oli->total = $validated['total'];

            // Simpan perubahan
            $oli->save();

            // Redirect kembali ke halaman sebelumnya atau ke halaman lain setelah update berhasil
            return redirect()->route('oli.index')->with('success', 'Data Oli berhasil diperbarui.');
        
        } catch (Exception $e) {
            // Jika terjadi error, tangani dan beri pesan error
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Cari menu berdasarkan ID
            $oli = Oli::findOrFail($id);

            // Hapus menu
            $oli->delete();

            // Mengembalikan response JSON dengan status sukses
            return response()->json([
                'success' => true,
                'message' => 'Data Oli has been deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Log error dan kembalikan error response JSON
            Log::error('Error deleting data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu. Please try again later. ' . $e->getMessage()
            ], 500); // Menggunakan status code 500 jika ada error server
        }
    }

    // Setup Oli
    public function updateHarga(Request $request)
    {
        $data = $request->input('oli');

        foreach ($data as $id => $harga) {
            $hargaOli = HargaOli::find($id);
            if ($hargaOli) {
                $hargaOli->update([
                    'harga' => $harga,
                    'updated_by' => Auth::user()->name,
                ]);
            }
        }

        return redirect()->route('oli.index')->with('success', 'Harga oli berhasil diperbarui');
    }

    public function download(Request $request)
    {
        // Validasi input bulan dan tahun
        $validated = $request->validate([
            'bulan' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
            'tahun' => 'required|digits:4|integer|min:2020|max:2099',
        ]);

        // Ambil data oli berdasarkan bulan dan tahun yang dipilih
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];

        // Filter data oli sesuai bulan dan tahun
        $dataOli = Oli::whereMonth('created_at', $bulan)
              ->whereYear('created_at', $tahun)
              ->select('id', 'created_at as tanggal', 'pengirim', 'jenis_oli', 'jumlah', 'harga', 'total')
              ->get();

        // Gunakan Laravel Excel untuk export ke Excel
        return Excel::download(new OliReportExport($dataOli), "report_oli_{$bulan}_{$tahun}.xlsx");
    }

}
