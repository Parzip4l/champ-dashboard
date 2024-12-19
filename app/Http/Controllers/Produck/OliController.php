<?php

namespace App\Http\Controllers\Produck;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product\Oli;
use Carbon\Carbon;
use App\Models\Setting\Slack;

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

        return view('general.inventory.oli.index', compact('oli', 'search', 'totalOliCurrent', 'percentChange', 'chartData', 'filter'));
    }


    public function create()
    {
        return view('general.inventory.oli.form');
    }

    public function store(Request $request)
    {
        try {
            $defaultReceive = 'Not Received';
            // Simpan data pembelian
            $olidata = new Oli();
            $olidata->tanggal = now();
            $olidata->pengirim = $request->pengirim;
            $olidata->jenis_oli = $request->jenis_oli;
            $olidata->jumlah = $request->jumlah;
            $olidata->receive_status = $defaultReceive;
            $olidata->save();

            $slackChannel = Slack::where('channel', 'Data Oli')->first();
            $slackWebhookUrl = $slackChannel->url;
            $today = now()->toDateString();
            $data = [
                'text' => "Data Pengiriman Oli",
                'attachments' => [
                    [
                        'title' => '',
                        'fields' => [
                            [
                                'title' => 'Tanggal',
                                'value' => now()->format('d F Y'),
                                'short' => true,
                            ],
                            [
                                'title' => 'Pengirim',
                                'value' => $request->pengirim,
                                'short' => true,
                            ],
                            [
                                'title' => 'Jenis Oli',
                                'value' => $request->jenis_oli,
                                'short' => true,
                            ],
                            [
                                'title' => 'Jumlah',
                                'value' => $request->jumlah,
                                'short' => true,
                            ],
                            [
                                'title' => 'Lihat Detail Data Di Champoil Portal',
                                'value' => '(https://dashboard.champoil.co.id/pencatatan-oli)',
                                'short' => true,
                            ]
                        ],
                    ],
                ],
                
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
                // Penanganan kesalahan jika Curl gagal
                $error = curl_error($ch);
                // Handle the error here
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack: ' . $error);
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode !== 200) {
                // Penanganan kesalahan jika Slack merespons selain status 200 OK
                // Handle the error here
                return redirect()->back()->with('error', 'Terjadi kesalahan saat mengirim data ke Slack. Kode status: ' . $httpCode);
            }

            curl_close($ch);
    
            return redirect()->back()->with('success', 'Data berhasil disimpan.');
        } catch (\Exception $e) {
            // Tangani kesalahan yang mungkin terjadi
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan Data: ' . $e->getMessage());
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

}
