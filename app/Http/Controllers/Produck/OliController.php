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

        // Query untuk mendapatkan data menu dengan filter pencarian jika ada
        $oli = Oli::when($search, function ($query, $search) {
            return $query->where('pengirim', 'like', '%' . $search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(50);

        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        $totalOli = [
            'Trafo' => Oli::where('jenis_oli', 'Trafo')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('jumlah'),
            'Bahan' => Oli::where('jenis_oli', 'Bahan')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('jumlah'),
            'Service' => Oli::where('jenis_oli', 'Service')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('jumlah'),
            'Minarex' => Oli::where('jenis_oli', 'Minarex')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('jumlah'),
        ];

        // Jika permintaan AJAX, kembalikan hanya bagian tampilan yang perlu diperbarui
        if ($request->ajax()) {
            return view('general.inventory.oli.index', compact('oli'))->render(); 
        }

        // Untuk tampilan biasa, kirimkan data menu
        return view('general.inventory.oli.index', compact('oli', 'search','totalOli'));
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

}
